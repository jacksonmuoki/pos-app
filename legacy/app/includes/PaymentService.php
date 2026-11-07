<?php
/*
 * Payment rules for POS sales: cash, M-Pesa (PayHero STK Push), or both.
 * An order is paid only when its successful payments cover the total.
 * Customer-facing messages are plain language. Technical detail goes to logs/payhero.log only.
 */

require_once __DIR__ . '/PayHeroClient.php';
require_once __DIR__ . '/Inventory.php';

final class PaymentService
{
    /** Minutes before an M-Pesa request still unresolved with PayHero is treated as timed out. */
    public const STALE_AFTER_MIN = 15;

    private const EPS = 0.005;

    public function __construct(private PDO $pdo, private array $config) {}

    public static function money(float $v): string
    {
        return 'KSh ' . number_format($v, 2);
    }

    /** Accepts 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX, 2547XXXXXXXX. Returns 2547XXXXXXXX or 2541XXXXXXXX. */
    public static function normalizePhone(string $input): ?string
    {
        $p = preg_replace('/[\s\-()]+/', '', $input) ?? '';
        if (str_starts_with($p, '+')) {
            $p = substr($p, 1);
        }
        if (preg_match('/^0([17]\d{8})$/', $p, $m)) {
            return '254' . $m[1];
        }
        if (preg_match('/^([17]\d{8})$/', $p, $m)) {
            return '254' . $m[1];
        }
        return preg_match('/^254[17]\d{8}$/', $p) ? $p : null;
    }

    /** Writes a technical log line. Never log credentials, the callback token, or full phone numbers. */
    public static function log(string $line): void
    {
        $file = dirname(__DIR__, 2) . '/logs/payhero.log';
        @file_put_contents($file, date('c') . ' ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** True while an M-Pesa request is pending or unresolved. Locks the order. */
    public function hasUnresolvedStk(int $orderId): bool
    {
        $s = $this->pdo->prepare(
            "SELECT 1 FROM payments WHERE order_id = ? AND status IN ('pending','unknown') LIMIT 1"
        );
        $s->execute([$orderId]);
        return (bool) $s->fetchColumn();
    }

    /** True once any payment (cash or M-Pesa) has been accepted on the order. */
    public function hasSuccessfulPayment(int $orderId): bool
    {
        $s = $this->pdo->prepare("SELECT 1 FROM payments WHERE order_id = ? AND status = 'success' LIMIT 1");
        $s->execute([$orderId]);
        return (bool) $s->fetchColumn();
    }

    /** @return array{due:float,paid:float,cash:float,mpesa:float,remaining:float} */
    public function summary(int $orderId): array
    {
        $o = $this->pdo->prepare('SELECT total, discount FROM orders WHERE id = ?');
        $o->execute([$orderId]);
        $ord = $o->fetch();
        $due = $ord ? max(0.0, round((float) $ord['total'] - (float) $ord['discount'], 2)) : 0.0;

        $s = $this->pdo->prepare(
            "SELECT payment_method, COALESCE(SUM(amount), 0) AS s
             FROM payments WHERE order_id = ? AND status = 'success' GROUP BY payment_method"
        );
        $s->execute([$orderId]);

        $cash = 0.0;
        $mpesa = 0.0;
        foreach ($s->fetchAll() as $r) {
            if ($r['payment_method'] === 'cash')  $cash  = (float) $r['s'];
            if ($r['payment_method'] === 'mpesa') $mpesa = (float) $r['s'];
        }

        $paid = round($cash + $mpesa, 2);

        return [
            'due'       => $due,
            'paid'      => $paid,
            'cash'      => round($cash, 2),
            'mpesa'     => round($mpesa, 2),
            'remaining' => max(0.0, round($due - $paid, 2)),
        ];
    }

    /* ---------- Cash ---------- */

    /**
     * Records cash against the order.
     * $applied is the part of the balance this cash pays. $tendered is the cash handed over.
     * Change is only given when the cash covers the whole remaining balance.
     * @return array{ok:bool,message:string,settled:bool,change:float,payment_id:?int}
     */
    public function recordCash(int $orderId, int $userId, float $applied, float $tendered): array
    {
        $applied  = round($applied, 2);
        $tendered = round($tendered, 2);

        if ($applied <= 0) {
            return self::cashResult(false, 'Enter the cash amount.');
        }
        if ($tendered + self::EPS < $applied) {
            return self::cashResult(false, 'Cash received is less than the cash amount.');
        }

        $this->pdo->beginTransaction();
        $settled = false;
        $change  = 0.0;
        $pid     = null;

        try {
            $lock = $this->pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
            $lock->execute([$orderId, $userId]);
            $order = $lock->fetch();

            if (!$order || $order['status'] !== 'open') {
                $this->pdo->rollBack();
                return self::cashResult(false, 'This order is already closed.');
            }
            if ($this->hasUnresolvedStk($orderId)) {
                $this->pdo->rollBack();
                return self::cashResult(false, 'An M-Pesa payment is still being confirmed. Please wait.');
            }

            $sum       = $this->summary($orderId);
            $remaining = $sum['remaining'];

            if ($remaining <= self::EPS) {
                $this->pdo->rollBack();
                return self::cashResult(false, 'This order is already fully paid.');
            }
            if ($applied > $remaining + self::EPS) {
                $this->pdo->rollBack();
                return self::cashResult(false, 'Cash is more than the balance of ' . self::money($remaining) . '.');
            }

            $change = round($tendered - $applied, 2);
            if ($change > self::EPS && $applied < $remaining - self::EPS) {
                $this->pdo->rollBack();
                return self::cashResult(false, 'Give change only when the cash covers the full balance.');
            }

            $ins = $this->pdo->prepare(
                "INSERT INTO payments
                    (order_id, user_id, external_reference, phone_number, amount,
                     payment_method, status, response_message, completed_at)
                 VALUES (?, ?, ?, 'CASH', ?, 'cash', 'success', 'Cash received', NOW())"
            );
            $ins->execute([
                $orderId,
                $userId,
                'CASH-' . $orderId . '-' . strtoupper(bin2hex(random_bytes(6))),
                $applied,
            ]);
            $pid = (int) $this->pdo->lastInsertId();

            $newRemaining = round($remaining - $applied, 2);
            if ($newRemaining <= self::EPS) {
                $settled = true;
                $this->settleOrder($orderId, $change);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        if ($settled) {
            $msg = 'Paid in full.';
            if ($change > self::EPS) {
                $msg .= ' Change: ' . self::money($change) . '.';
            }
        } else {
            $msg = 'Cash of ' . self::money($applied) . ' received. Balance due '
                 . self::money(round($remaining - $applied, 2)) . '.';
        }

        return [
            'ok'         => true,
            'message'    => $msg,
            'settled'    => $settled,
            'change'     => $settled ? $change : 0.0,
            'payment_id' => $pid,
        ];
    }

    /* ---------- M-Pesa ---------- */

    /** @return array{ok:bool,message:string,payment_id:?int,settled:bool} */
    public function startStk(int $orderId, int $userId, string $phoneInput, int $amount, string $customerName): array
    {
        $phone = self::normalizePhone($phoneInput);
        if ($phone === null) {
            return self::result(false, 'Enter a valid M-Pesa number, for example 0712 345 678.');
        }
        if ($amount < 1) {
            return self::result(false, 'Enter the M-Pesa amount in whole shillings.');
        }

        // Throws if the PayHero setup is incomplete. The caller shows a safe message and logs details.
        $client = new PayHeroClient($this->config);

        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
            $lock->execute([$orderId, $userId]);
            $order = $lock->fetch();

            if (!$order || $order['status'] !== 'open') {
                $this->pdo->rollBack();
                return self::result(false, 'This order is already closed.');
            }
            if ($this->hasUnresolvedStk($orderId)) {
                $this->pdo->rollBack();
                return self::result(false, 'An M-Pesa payment is still being confirmed. Please wait.');
            }

            $sum = $this->summary($orderId);
            if ($sum['remaining'] <= self::EPS) {
                $this->pdo->rollBack();
                return self::result(false, 'This order is already fully paid.');
            }
            if ($amount > $sum['remaining'] + self::EPS) {
                $this->pdo->rollBack();
                return self::result(false, 'The M-Pesa amount is more than the balance of ' . self::money($sum['remaining']) . '.');
            }

            $ref = 'POS-' . $orderId . '-' . strtoupper(bin2hex(random_bytes(5)));

            $ins = $this->pdo->prepare(
                "INSERT INTO payments
                    (order_id, user_id, external_reference, phone_number, amount,
                     payment_method, status, response_message)
                 VALUES (?, ?, ?, ?, ?, 'mpesa', 'pending', 'Sending M-Pesa prompt')"
            );
            $ins->execute([$orderId, $userId, $ref, $phone, $amount]);
            $paymentId = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        // The network call runs outside the transaction, so no database lock is held during it.
        try {
            $resp = $client->initiateStk($amount, $phone, $ref, $customerName);

            $this->markPayment($paymentId, [
                'payhero_reference'   => $resp['reference'] ?? null,
                'checkout_request_id' => $resp['CheckoutRequestID'] ?? $resp['checkout_request_id'] ?? null,
                'response_message'    => 'Waiting for the customer to enter their M-Pesa PIN.',
            ]);
            self::log("stk accepted payment={$paymentId} order={$orderId} amount={$amount}");

            return self::result(true, 'M-Pesa prompt sent. Ask the customer to enter their PIN.', $paymentId);
        } catch (PayHeroRejected $e) {
            $this->markPayment($paymentId, [
                'status'           => 'failed',
                'response_message' => 'The M-Pesa request could not be sent. Check the number and try again.',
            ]);
            self::log("stk rejected payment={$paymentId} order={$orderId}: " . $e->getMessage());

            return self::result(false, 'The M-Pesa request could not be sent. Check the number and try again, or take cash.', $paymentId);
        } catch (Throwable $e) {
            $this->markPayment($paymentId, [
                'status'           => 'unknown',
                'response_message' => 'Checking the M-Pesa request. Please wait.',
            ]);
            self::log("stk unknown payment={$paymentId} order={$orderId}: " . $e->getMessage());

            return self::result(false, 'We could not confirm the M-Pesa prompt. Wait a moment before trying again.', $paymentId);
        }
    }

    /* ---------- Callback and reconciliation ---------- */

    /** Called by the webhook. */
    public function handleCallback(array $payload): array
    {
        $payload = self::normalizeCallback($payload);

        $ref = (string) ($payload['external_reference'] ?? '');
        if ($ref === '') {
            self::log('callback without external_reference: ' . substr((string) json_encode($payload), 0, 300));
            return self::result(false, 'Missing external_reference.');
        }
        return $this->apply($ref, $payload, 'callback');
    }

    /** Called by cron. Checks PayHero for payments pending or unknown for over 2 minutes. */
    public function reconcileStale(int $limit = 50): int
    {
        $rows = $this->pdo->query(
            "SELECT *, TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS age_min
             FROM payments
             WHERE payment_method = 'mpesa'
               AND status IN ('pending','unknown')
               AND created_at < NOW() - INTERVAL 2 MINUTE
             ORDER BY id
             LIMIT " . (int) $limit
        )->fetchAll();

        $client = new PayHeroClient($this->config);
        $count  = 0;

        foreach ($rows as $pay) {
            $count++;
            $stale     = (int) $pay['age_min'] >= self::STALE_AFTER_MIN;
            $reference = (string) ($pay['payhero_reference'] ?: $pay['external_reference']);

            try {
                $resp = $client->getTransactionStatus($reference);
            } catch (PayHeroRejected $e) {
                // Not verifiable. Send it to a person rather than failing it automatically.
                if ($stale && $pay['status'] !== 'review') {
                    $this->markPayment((int) $pay['id'], [
                        'status'           => 'review',
                        'response_message' => 'Could not confirm this M-Pesa payment. A manager needs to check it.',
                    ]);
                    self::log("reconcile needs review payment={$pay['id']}: " . $e->getMessage());
                }
                continue;
            } catch (PayHeroUnavailable $e) {
                self::log("reconcile unavailable payment={$pay['id']}: " . $e->getMessage());
                continue;
            }

            $resp    = self::normalizeCallback($resp);
            $outcome = self::outcome($resp);

            if ($outcome === 'pending' && $stale) {
                $this->apply($pay['external_reference'], [
                    'status'     => 'FAILED',
                    'ResultDesc' => 'Timed out waiting for customer confirmation.',
                ], 'reconcile');
            } elseif ($outcome !== 'pending') {
                $this->apply($pay['external_reference'], $resp, 'reconcile');
            }
        }

        return $count;
    }

    /**
     * Applies an M-Pesa result. Locks the payment and the order, is idempotent,
     * and closes the order only when all payments cover the total.
     */
    private function apply(string $ref, array $payload, string $source): array
    {
        $outcome = self::outcome($payload);
        $raw     = (string) json_encode($payload, JSON_UNESCAPED_SLASHES);

        $this->pdo->beginTransaction();
        try {
            $s = $this->pdo->prepare('SELECT * FROM payments WHERE external_reference = ? FOR UPDATE');
            $s->execute([$ref]);
            $pay = $s->fetch();

            if (!$pay) {
                $this->pdo->commit();
                self::log("{$source}: unknown reference {$ref}");
                return self::result(false, 'Unknown payment reference.');
            }

            $pid = (int) $pay['id'];
            $oid = (int) $pay['order_id'];

            if ($pay['status'] === 'success') {
                $this->pdo->commit();
                return self::result(true, 'Already confirmed.', $pid);
            }

            if ($outcome === 'pending') {
                $this->markPayment($pid, ['raw_callback' => $raw]);
                $this->pdo->commit();
                return self::result(true, 'Still waiting for the customer.', $pid);
            }

            if ($outcome === 'failed') {
                $this->markPayment($pid, [
                    'status'           => 'failed',
                    'response_message' => self::customerFailMessage($payload),
                    'raw_callback'     => $raw,
                ]);
                $this->pdo->commit();
                self::log("{$source}: payment {$pid} failed: " . (string) ($payload['ResultDesc'] ?? ''));
                return self::result(true, 'M-Pesa payment was not completed.', $pid);
            }

            // Success path
            $receipt = (string) ($payload['mpesa_receipt_number'] ?? $payload['MpesaReceiptNumber'] ?? '');
            $paidAmt = (float) ($payload['amount'] ?? $payload['Amount'] ?? 0);

            if ($paidAmt > 0 && abs($paidAmt - (float) $pay['amount']) > self::EPS) {
                $this->markPayment($pid, [
                    'status'           => 'review',
                    'response_message' => 'The amount did not match. A manager needs to check this payment.',
                    'raw_callback'     => $raw,
                ]);
                $this->pdo->commit();
                self::log("{$source}: amount mismatch payment {$pid}");
                return self::result(false, 'Amount mismatch. Sent for review.', $pid);
            }

            $lock = $this->pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
            $lock->execute([$oid]);
            $orderStatus = $lock->fetchColumn();

            if ($orderStatus !== 'open') {
                $this->markPayment($pid, [
                    'status'           => 'review',
                    'mpesa_receipt'    => $receipt !== '' ? $receipt : null,
                    'response_message' => 'Received after the order was closed. A manager needs to check this payment.',
                    'raw_callback'     => $raw,
                ]);
                $this->pdo->commit();
                self::log("{$source}: payment {$pid} received after order closed");
                return self::result(false, 'Received after the order was closed. Sent for review.', $pid);
            }

            $this->markPayment($pid, [
                'status'           => 'success',
                'mpesa_receipt'    => $receipt !== '' ? $receipt : null,
                'response_message' => 'M-Pesa payment received.',
                'raw_callback'     => $raw,
            ]);

            $sum = $this->summary($oid);

            if ($sum['remaining'] <= self::EPS) {
                $this->settleOrder($oid, 0.0);
                $this->markPayment($pid, ['response_message' => 'M-Pesa payment received. Order paid in full.']);
                $msg = 'Paid in full.';
            } else {
                $this->markPayment($pid, [
                    'response_message' => 'M-Pesa payment received. Balance due ' . self::money($sum['remaining']) . '.',
                ]);
                $msg = 'Part paid. Balance due ' . self::money($sum['remaining']) . '.';
            }

            $this->pdo->commit();
            self::log("{$source}: payment {$pid} success, remaining=" . $sum['remaining']);

            return self::result(true, $msg, $pid);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /* ---------- Internals ---------- */

    /**
     * Marks the order paid. Must run inside a transaction with the order row locked.
     * Method: cash, mpesa, or split (both).
     */
        private function settleOrder(int $orderId, float $change): void
    {
        $sum    = $this->summary($orderId);
        $method = ($sum['cash'] > self::EPS && $sum['mpesa'] > self::EPS)
            ? 'split'
            : ($sum['mpesa'] > self::EPS ? 'mpesa' : 'cash');

        $r = $this->pdo->prepare(
            "SELECT mpesa_receipt FROM payments
             WHERE order_id = ? AND status = 'success' AND payment_method = 'mpesa' AND mpesa_receipt IS NOT NULL
             ORDER BY id DESC LIMIT 1"
        );
        $r->execute([$orderId]);
        $ref = $r->fetchColumn() ?: null;

        $u = $this->pdo->prepare(
            "UPDATE orders
             SET status = 'paid', paid_at = NOW(), payment_method = ?,
                 amount_tendered = ?, change_due = ?, mpesa_ref = ?
             WHERE id = ? AND status = 'open'"
        );
        $u->execute([$method, round($sum['paid'] + $change, 2), round($change, 2), $ref, $orderId]);

        // Deduct stock once, only if this call actually changed the order to paid.
        if ($u->rowCount() === 1) {
            Inventory::deductForOrder($this->pdo, $orderId);
        }
    }

    /** Plain-language reason for a failed M-Pesa payment. Technical detail stays in the log. */
    private static function customerFailMessage(array $p): string
    {
        $code = (string) ($p['ResultCode'] ?? $p['result_code'] ?? '');

        return match ($code) {
            '1032'  => 'The customer cancelled the M-Pesa prompt. Try again or take cash.',
            '1037'  => 'The customer did not respond to the M-Pesa prompt. Try again or take cash.',
            '1'     => 'The customer has insufficient M-Pesa balance. Try a smaller amount or take cash.',
            '2001'  => 'The M-Pesa PIN was wrong. Try again.',
            default => 'The M-Pesa payment was not completed. Try again or take cash.',
        };
    }

    /** Maps PayHero's PascalCase callback fields onto the names used internally. */
    private static function normalizeCallback(array $p): array
    {
        $map = [
            'external_reference'   => ['ExternalReference', 'external_reference'],
            'status'               => ['Status', 'status'],
            'ResultCode'           => ['ResultCode', 'result_code'],
            'ResultDesc'           => ['ResultDesc', 'message'],
            'amount'               => ['Amount', 'amount'],
            'mpesa_receipt_number' => ['MpesaReceiptNumber', 'mpesa_receipt_number'],
        ];

        $out = $p;
        foreach ($map as $target => $sources) {
            if (isset($out[$target]) && $out[$target] !== '') {
                continue;
            }
            foreach ($sources as $src) {
                if (isset($p[$src]) && $p[$src] !== '') {
                    $out[$target] = $p[$src];
                    break;
                }
            }
        }
        return $out;
    }

    /** Maps a PayHero payload to success | failed | pending. */
    private static function outcome(array $p): string
    {
        $status = strtoupper((string) ($p['status'] ?? $p['Status'] ?? ''));
        $code   = $p['ResultCode'] ?? $p['result_code'] ?? null;

        if ($status === 'SUCCESS' || ($code !== null && (string) $code === '0')) {
            return 'success';
        }
        if (in_array($status, ['FAILED', 'CANCELLED', 'CANCELED', 'REJECTED', 'TIMEOUT', 'EXPIRED'], true)) {
            return 'failed';
        }
        if ($code !== null && (string) $code !== '') {
            return 'failed';
        }
        return 'pending';
    }

    /** Updates only whitelisted columns. */
    private function markPayment(int $id, array $fields): void
    {
        $allowed = ['status', 'payhero_reference', 'checkout_request_id', 'response_message', 'mpesa_receipt', 'raw_callback'];
        $sets = [];
        $vals = [];

        foreach ($fields as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $sets[] = "{$col} = ?";
                $vals[] = $val;
            }
        }
        if (!$sets) {
            return;
        }

        $vals[] = $id;
        $this->pdo->prepare('UPDATE payments SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
    }

    private static function result(bool $ok, string $message, ?int $paymentId = null): array
    {
        return ['ok' => $ok, 'message' => $message, 'payment_id' => $paymentId, 'settled' => false];
    }

    private static function cashResult(bool $ok, string $message): array
    {
        return ['ok' => $ok, 'message' => $message, 'settled' => false, 'change' => 0.0, 'payment_id' => null];
    }
}