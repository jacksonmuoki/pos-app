<?php
/* Cashier workspace: orders, products, ticket, payments (cash, M-Pesa, or both) and receipt.
   Payment rules live in PaymentService. This page starts actions and shows the result. */

require_once dirname(__DIR__, 2) . '/includes/PaymentService.php';

$h        = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pdo      = db();
$payments = new PaymentService($pdo, require dirname(__DIR__, 2) . '/includes/payhero.php');

$me       = $_SESSION['user'];
$meId     = (int) $me['id'];
$shop     = setting('shop_name', 'POS');
$shopAddr = setting('shop_address', '');
$shopTel  = setting('shop_phone', '');
$shopPin  = setting('shop_pin', '');
$footer   = setting('receipt_footer', 'Thank you for your business!');
$cur      = setting('currency', 'KES');
$taxRate  = (float) setting('tax_rate', '0');
$taxLabel = setting('tax_label', 'VAT');
$taxPct   = rtrim(rtrim(number_format($taxRate, 2), '0'), '.');
$money    = fn($v) => $cur . ' ' . number_format((float) $v, 2);
$initial  = strtoupper(mb_substr($shop, 0, 1));
$meInit   = strtoupper(mb_substr($me['name'], 0, 1));

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

/* ---------- Helpers: every lookup is limited to this user ---------- */
function own_order(PDO $pdo, int $id, int $uid): ?array
{
    $s = $pdo->prepare('SELECT o.*, u.name AS staff FROM orders o
                        LEFT JOIN users u ON u.id = o.user_id
                        WHERE o.id = ? AND o.user_id = ?');
    $s->execute([$id, $uid]);
    return $s->fetch() ?: null;
}

function order_items(PDO $pdo, int $oid): array
{
    $s = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $s->execute([$oid]);
    return $s->fetchAll();
}

function order_payments(PDO $pdo, int $oid): array
{
    $s = $pdo->prepare("SELECT payment_method, amount, mpesa_receipt FROM payments
                        WHERE order_id = ? AND status = 'success' ORDER BY id");
    $s->execute([$oid]);
    return $s->fetchAll();
}

function recalc_total(PDO $pdo, int $oid): void
{
    $pdo->prepare('UPDATE orders SET total = (SELECT COALESCE(SUM(qty * price), 0)
                   FROM order_items WHERE order_id = ?) WHERE id = ?')->execute([$oid, $oid]);
    $pdo->prepare('UPDATE orders SET discount = LEAST(discount, total) WHERE id = ?')->execute([$oid]);
}

function due_of(array $o): float
{
    return max(0.0, (float) $o['total'] - (float) $o['discount']);
}

function ago(string $ts): string
{
    $m = (int) floor((time() - strtotime($ts)) / 60);
    if ($m < 1) return 'just now';
    if ($m < 60) return $m . ' min';
    return intdiv($m, 60) . 'h ' . ($m % 60) . 'm';
}

/* Why an open order cannot be changed right now, or null when it can. */
function edit_block(PaymentService $payments, PDO $pdo, int $oid, int $uid): ?string
{
    $o = $oid ? own_order($pdo, $oid, $uid) : null;
    if (!$o || $o['status'] !== 'open') return null;
    if ($payments->hasUnresolvedStk($oid)) return 'Please wait. An M-Pesa payment is still being confirmed.';
    if ($payments->hasSuccessfulPayment($oid)) return 'Payment has started on this order, so it can no longer change.';
    return null;
}

/* ---------- POST actions (Post/Redirect/Get) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid    = (int) ($_POST['order_id'] ?? 0);
    $target = BASE_URL . 'cashier' . ($oid ? '?order=' . $oid : '');
    $flash  = null;

    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $flash = ['error', 'Your session expired. Please try again.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');
        try {
            switch ($action) {
                case 'new':
                    $table = mb_substr(trim((string) ($_POST['table_no'] ?? '')), 0, 20);
                    $pdo->prepare("INSERT INTO orders (user_id, status, table_no, total, discount, created_at)
                                   VALUES (?, 'open', ?, 0, 0, NOW())")
                        ->execute([$meId, $table !== '' ? $table : null]);
                    $oid    = (int) $pdo->lastInsertId();
                    $target = BASE_URL . 'cashier?order=' . $oid;
                    $flash  = ['success', 'Order #' . $oid . ' opened.'];
                    break;

                case 'add':
                    $p = $pdo->prepare('SELECT id, name, price FROM products WHERE id = ? AND active = 1');
                    $p->execute([(int) ($_POST['product_id'] ?? 0)]);
                    $product = $p->fetch();
                    if (!$product) { $flash = ['error', 'That product is not available.']; break; }

                    if ($block = edit_block($payments, $pdo, $oid, $meId)) { $flash = ['error', $block]; break; }

                    $order = $oid ? own_order($pdo, $oid, $meId) : null;
                    if (!$order || $order['status'] !== 'open') {
                        $pdo->prepare("INSERT INTO orders (user_id, status, total, discount, created_at)
                                       VALUES (?, 'open', 0, 0, NOW())")->execute([$meId]);
                        $oid = (int) $pdo->lastInsertId();
                    }
                    $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, price, qty)
                                   VALUES (?, ?, ?, ?, 1)
                                   ON DUPLICATE KEY UPDATE qty = LEAST(99, qty + 1)')
                        ->execute([$oid, $product['id'], $product['name'], $product['price']]);
                    recalc_total($pdo, $oid);
                    $target = BASE_URL . 'cashier?order=' . $oid;
                    break;

                case 'qty':
                    $order = own_order($pdo, $oid, $meId);
                    if (!$order || $order['status'] !== 'open') { $flash = ['error', 'This order is closed.']; break; }
                    if ($block = edit_block($payments, $pdo, $oid, $meId)) { $flash = ['error', $block]; break; }

                    $item = (int) ($_POST['item_id'] ?? 0);
                    $qty  = (int) ($_POST['qty'] ?? 0);
                    if ($qty <= 0) {
                        $pdo->prepare('DELETE FROM order_items WHERE id = ? AND order_id = ?')->execute([$item, $oid]);
                    } else {
                        $pdo->prepare('UPDATE order_items SET qty = ? WHERE id = ? AND order_id = ?')
                            ->execute([min(99, $qty), $item, $oid]);
                    }
                    recalc_total($pdo, $oid);
                    $target = BASE_URL . 'cashier?order=' . $oid;
                    break;

                case 'rename':
                    $order = own_order($pdo, $oid, $meId);
                    if (!$order || $order['status'] !== 'open') { $flash = ['error', 'This order is closed.']; break; }
                    if ($block = edit_block($payments, $pdo, $oid, $meId)) { $flash = ['error', $block]; break; }

                    $table = mb_substr(trim((string) ($_POST['table_no'] ?? '')), 0, 20);
                    $pdo->prepare('UPDATE orders SET table_no = ? WHERE id = ? AND user_id = ?')
                        ->execute([$table !== '' ? $table : null, $oid, $meId]);
                    $target = BASE_URL . 'cashier?order=' . $oid;
                    $flash  = ['success', 'Table or customer updated.'];
                    break;

                case 'discount':
                    $order = own_order($pdo, $oid, $meId);
                    if (!$order || $order['status'] !== 'open') { $flash = ['error', 'This order is closed.']; break; }
                    if ($block = edit_block($payments, $pdo, $oid, $meId)) { $flash = ['error', $block]; break; }

                    $amt = round(min(max(0.0, (float) ($_POST['discount'] ?? 0)), (float) $order['total']), 2);
                    $pdo->prepare('UPDATE orders SET discount = ? WHERE id = ? AND user_id = ?')
                        ->execute([$amt, $oid, $meId]);
                    $target = BASE_URL . 'cashier?order=' . $oid;
                    $flash  = ['success', $amt > 0 ? 'Discount of ' . $money($amt) . ' applied.' : 'Discount removed.'];
                    break;

                case 'hold':
                    if ($block = edit_block($payments, $pdo, $oid, $meId)) { $flash = ['error', $block]; break; }

                    $u = $pdo->prepare("UPDATE orders SET status = 'held' WHERE id = ? AND user_id = ? AND status = 'open'");
                    $u->execute([$oid, $meId]);
                    if ($u->rowCount() === 0) { $flash = ['error', 'This order cannot be held.']; break; }
                    $target = BASE_URL . 'cashier';
                    $flash  = ['success', 'Order #' . $oid . ' put on hold.'];
                    break;

                case 'resume':
                    $u = $pdo->prepare("UPDATE orders SET status = 'open' WHERE id = ? AND user_id = ? AND status = 'held'");
                    $u->execute([$oid, $meId]);
                    if ($u->rowCount() === 0) { $flash = ['error', 'This order cannot be resumed.']; break; }
                    $target = BASE_URL . 'cashier?order=' . $oid;
                    $flash  = ['success', 'Order #' . $oid . ' resumed.'];
                    break;

                case 'cancel':
                    if ($payments->hasUnresolvedStk($oid)) {
                        $flash = ['error', 'Please wait. An M-Pesa payment is still being confirmed.'];
                        break;
                    }
                    if ($payments->hasSuccessfulPayment($oid)) {
                        $flash = ['error', 'This order has payments and cannot be cancelled here. Ask a manager.'];
                        break;
                    }

                    $u = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('open','held')");
                    $u->execute([$oid, $meId]);
                    if ($u->rowCount() === 0) { $flash = ['error', 'This order is already closed.']; break; }
                    $target = BASE_URL . 'cashier';
                    $flash  = ['success', 'Order #' . $oid . ' cancelled.'];
                    break;

                case 'pay':
                    $order = own_order($pdo, $oid, $meId);
                    if (!$order || $order['status'] !== 'open') {
                        $flash = ['error', 'This order is already closed.'];
                        break;
                    }

                    $method   = (string) ($_POST['method'] ?? '');
                    $printNow = !empty($_POST['print']);
                    $r        = null;

                    try {
                        if ($method === 'cash') {
                            $r = $payments->recordCash(
                                $oid,
                                $meId,
                                (float) str_replace(',', '', (string) ($_POST['cash_applied'] ?? '0')),
                                (float) str_replace(',', '', (string) ($_POST['cash_tendered'] ?? '0'))
                            );
                        } elseif ($method === 'mpesa') {
                            $r = $payments->startStk(
                                $oid,
                                $meId,
                                (string) ($_POST['phone_number'] ?? ''),
                                (int) ($_POST['mpesa_amount'] ?? 0),
                                (string) ($me['name'] ?? 'POS Customer')
                            );
                        } else {
                            $flash = ['error', 'Choose cash or M-Pesa.'];
                            break;
                        }
                    } catch (Throwable $e) {
                        // Setup problems are logged in detail, shown in plain words.
                        error_log('Payment start failed for order ' . $oid . ': ' . $e->getMessage());
                        $r = ['ok' => false, 'settled' => false, 'message' => 'Payment could not be processed. Try again or take cash.'];
                    }

                    $flash = [$r['ok'] ? 'success' : 'error', $r['message']];

                    // Print the receipt when the order is fully paid, if Print was ticked.
                    $_SESSION['autoprint'][$oid] = $printNow;

                    $target = BASE_URL . 'cashier?order=' . $oid
                        . (!empty($r['settled']) ? '&receipt=' . $oid : '');
                    break;
            }
        } catch (PDOException $e) {
            error_log('Cashier error: ' . $e->getMessage());
            $flash = ['error', 'Could not save. Please try again.'];
        }
    }

    if ($flash) $_SESSION['flash'] = ['type' => $flash[0], 'msg' => $flash[1]];
    header('Location: ' . $target);
    exit;
}

/* ---------- GET: data for this user ---------- */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$receiptId  = (int) ($_GET['receipt'] ?? 0);
$receiptOrd = $receiptId ? own_order($pdo, $receiptId, $meId) : null;
if (!$receiptOrd) $receiptId = 0;
$rItems = $receiptOrd ? order_items($pdo, $receiptId) : [];
$rPays  = $receiptOrd ? order_payments($pdo, $receiptId) : [];

// Print once, only for a fully paid receipt, if the payment was started with Print ticked.
$autoPrint = false;
if ($receiptId && $receiptOrd['status'] === 'paid' && isset($_SESSION['autoprint'][$receiptId])) {
    $autoPrint = (bool) $_SESSION['autoprint'][$receiptId];
    unset($_SESSION['autoprint'][$receiptId]);
}

$selectedId = (int) ($_GET['order'] ?? 0) ?: $receiptId;
$selected   = $selectedId ? own_order($pdo, $selectedId, $meId) : null;
if (!$selected) $selectedId = 0;

$items     = $selected ? order_items($pdo, $selectedId) : [];
$selIsOpen = $selected && $selected['status'] === 'open';
$selHeld   = $selected && $selected['status'] === 'held';
$selDue    = $selected ? due_of($selected) : 0.0;
$sum       = $selected ? $payments->summary($selectedId) : null;
$selPaid      = $sum['paid']      ?? 0.0;
$selRemaining = $sum['remaining'] ?? 0.0;
$selPending   = $selIsOpen && $payments->hasUnresolvedStk($selectedId);
$editLocked   = $selIsOpen && ($selPending || $selPaid > 0);
$selTax    = ($selected && $taxRate > 0) ? $selDue * $taxRate / (100 + $taxRate) : 0.0;
$inCart    = [];
if ($selIsOpen) foreach ($items as $it) $inCart[(int) $it['product_id']] = (int) $it['qty'];

// Latest payment attempt, for the status banner.
$lastPay = null;
if ($selectedId) {
    $lp = $pdo->prepare('SELECT payment_method, status, response_message FROM payments
                         WHERE order_id = ? ORDER BY id DESC LIMIT 1');
    $lp->execute([$selectedId]);
    $lastPay = $lp->fetch() ?: null;
}

// Status banner: plain language only.
$banner = null;
if ($selIsOpen) {
    if ($selPending) {
        $banner = ($lastPay['status'] ?? '') === 'unknown'
            ? ['wait', 'Checking the M-Pesa payment', 'Please wait. Items and payment stay locked until it is confirmed.']
            : ['wait', 'Waiting for M-Pesa PIN',
               'Ask the customer to enter their PIN for ' . PaymentService::money($selRemaining > 0 ? (float) ($lastPay['amount'] ?? 0) : 0) . '. This page updates automatically.'];
    } elseif ($lastPay && $lastPay['status'] === 'failed') {
        $banner = ['bad', 'M-Pesa payment not completed', $lastPay['response_message'] ?: 'Try again or take cash.'];
    } elseif ($lastPay && $lastPay['status'] === 'review') {
        $banner = ['bad', 'Payment needs a manager check', $lastPay['response_message'] ?: 'Ask a manager to check this payment.'];
    } elseif ($selPaid > 0 && $selRemaining > 0) {
        $banner = ['info', 'Part paid',
            'Balance due ' . PaymentService::money($selRemaining) . '. Take the rest in cash or M-Pesa. Items can no longer change.'];
    }
}

$paper = in_array($_GET['paper'] ?? '', ['58', '80'], true)
    ? $_GET['paper']
    : (setting('receipt_paper', '80') === '58' ? '58' : '80');

$products   = $pdo->query('SELECT id, name, category, price FROM products WHERE active = 1 ORDER BY category, name')->fetchAll();
$categories = array_values(array_unique(array_column($products, 'category')));

$qSql = "SELECT o.id, o.table_no, o.total, o.discount, o.created_at,
                (SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
         FROM orders o WHERE o.user_id = ? AND o.status = ? ORDER BY o.created_at ASC LIMIT 60";
$qs = $pdo->prepare($qSql);
$qs->execute([$meId, 'open']); $openOrders = $qs->fetchAll();
$qs->execute([$meId, 'held']); $heldOrders = $qs->fetchAll();

// Today's sales. Cash and M-Pesa come from the payments, so split orders count correctly.
$ts = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(total - discount),0) AS sales
                     FROM orders WHERE user_id = ? AND status = 'paid' AND paid_at >= ?");
$ts->execute([$meId, date('Y-m-d 00:00:00')]);
$today = $ts->fetch();
$today['cash']  = 0.0;
$today['mpesa'] = 0.0;

$pm = $pdo->prepare("SELECT p.payment_method AS m, COALESCE(SUM(p.amount),0) AS s
                     FROM payments p JOIN orders o ON o.id = p.order_id
                     WHERE o.user_id = ? AND o.status = 'paid' AND o.paid_at >= ? AND p.status = 'success'
                     GROUP BY p.payment_method");
$pm->execute([$meId, date('Y-m-d 00:00:00')]);
foreach ($pm->fetchAll() as $row) {
    if ($row['m'] === 'cash')  $today['cash']  += (float) $row['s'];
    if ($row['m'] === 'mpesa') $today['mpesa'] += (float) $row['s'];
}

// Orders paid before payment rows existed.
foreach (['cash', 'mpesa'] as $legacy) {
    $lg = $pdo->prepare("SELECT COALESCE(SUM(o.total - o.discount),0) FROM orders o
                         WHERE o.user_id = ? AND o.status = 'paid' AND o.paid_at >= ?
                           AND o.payment_method = ?
                           AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.order_id = o.id AND p.status = 'success')");
    $lg->execute([$meId, date('Y-m-d 00:00:00'), $legacy]);
    $today[$legacy] += (float) $lg->fetchColumn();
}

$rs = $pdo->prepare("SELECT id, total, discount, payment_method, paid_at FROM orders
                     WHERE user_id = ? AND status = 'paid' ORDER BY paid_at DESC LIMIT 10");
$rs->execute([$meId]);
$recentPaid = $rs->fetchAll();

$postUrl   = BASE_URL . 'cashier' . ($selectedId ? '?order=' . $selectedId : '');
$csrfField = '<input type="hidden" name="csrf" value="' . $h($csrf) . '">';
$orderUrl  = fn(int $id) => BASE_URL . 'cashier?order=' . $id;
$rcUrl     = fn(int $id, string $p = '') => BASE_URL . 'cashier?receipt=' . $id . '&order=' . $id . ($p ? '&paper=' . $p : '');
$closeUrl  = $selectedId ? $orderUrl($selectedId) : BASE_URL . 'cashier';

// Quick cash buttons: exact balance, then common notes above it.
$quick = [round($selRemaining, 2)];
foreach ([500, 1000, 2000, 5000] as $note) if ($note > $selRemaining) $quick[] = $note;
$quick = array_slice(array_values(array_unique($quick)), 0, 4);

// Receipt figures
if ($receiptOrd) {
    $rDue     = due_of($receiptOrd);
    $rTax     = $taxRate > 0 ? $rDue * $taxRate / (100 + $taxRate) : 0.0;
    $rNo      = 'RCP-' . str_pad((string) $receiptId, 6, '0', STR_PAD_LEFT);
    $rPaid    = $receiptOrd['status'] === 'paid';
    $rPaidSum = round((float) array_sum(array_column($rPays, 'amount')), 2);
    $rBal     = max(0.0, round($rDue - $rPaidSum, 2));
    $rRef     = '';
    foreach ($rPays as $rp) {
        if ($rp['payment_method'] === 'mpesa' && !empty($rp['mpesa_receipt'])) $rRef = $rp['mpesa_receipt'];
    }
    $rQr = $shop . ' | ' . $rNo . ' | ' . $cur . ' ' . number_format($rDue, 2, '.', '') . ($rRef !== '' ? ' | ' . $rRef : '');
}

$rcCss = <<<'CSS'
.rc{background:#fff;color:#000;font-family:"Courier New",ui-monospace,Menlo,Consolas,monospace;font-size:12px;line-height:1.35;width:80mm;max-width:100%;padding:5mm 4mm 4mm;font-weight:600}
.rc.p58{width:58mm;font-size:10.5px;padding:4mm 3mm 3mm}
.rc *{box-sizing:border-box}
.rc .c{text-align:center}
.rc .shop{font-size:1.5em;font-weight:800;text-transform:uppercase;letter-spacing:.05em;line-height:1.15;margin-bottom:3px}
.rc .sub{font-weight:600;font-size:.95em}
.rc .sep{border:0;border-top:1px dashed #000;margin:6px 0}
.rc .sep.solid{border-top:2px solid #000}
.rc .row{display:flex;justify-content:space-between;gap:8px;align-items:baseline}
.rc .row>span:last-child{text-align:right;white-space:nowrap}
.rc .title{text-align:center;font-weight:800;letter-spacing:.2em;text-transform:uppercase;margin:2px 0}
.rc .cols{display:flex;justify-content:space-between;font-weight:800;border-bottom:1px solid #000;padding-bottom:2px;margin-bottom:3px}
.rc .it{margin:0 0 4px}
.rc .it .nm{font-weight:700;word-break:break-word}
.rc .it .calc{display:flex;justify-content:space-between;padding-left:2ch}
.rc .cnt{font-weight:700}
.rc .grand{display:flex;justify-content:space-between;align-items:baseline;font-size:1.55em;font-weight:800;border-top:2px solid #000;border-bottom:2px solid #000;padding:3px 0;margin:6px 0}
.rc .stamp{border:2px solid #000;text-align:center;padding:3px 0;margin:6px 0;font-weight:800;letter-spacing:.12em}
.rc .qr{display:flex;justify-content:center;margin:8px 0 2px}
.rc .qr svg{width:26mm;height:26mm}
.rc.p58 .qr svg{width:22mm;height:22mm}
.rc .bc{margin:8px 0 2px}
.rc .bc svg{display:block;width:100%;height:12mm}
.rc .bc small{display:block;text-align:center;letter-spacing:.15em;margin-top:2px;font-size:.9em}
.rc .foot{text-align:center;font-weight:700;margin-top:4px}
.rc .fine{text-align:center;font-weight:600;font-size:.88em;margin-top:2px}
CSS;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#14532d">
<title>Point of Sale | <?= $h($shop) ?></title>
<style>
:root{
  --bg:#f0fdf4; --card:#ffffff; --text:#052e16; --mut:#4b5d52; --faint:#789083; --line:#d1fae5; --soft:#f7fdf8;
  --brand:#16a34a; --brand-d:#166534; --brand-s:#dcfce7; --brand-l:#86efac;
  --ok:#15803d; --ok-s:#dcfce7; --amb:#a16207; --amb-s:#fef3c7; --red:#b91c1c; --red-s:#fef2f2;
  --r:16px; --r-s:10px; --sh:0 1px 2px rgba(5,46,22,.05),0 8px 24px -18px rgba(5,46,22,.28);
  --mono:ui-monospace,"SF Mono",Menlo,Consolas,monospace; --safe-b:env(safe-area-inset-bottom,0px);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
[hidden]{display:none!important}
html,body{height:100%}
body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.45;-webkit-font-smoothing:antialiased;-webkit-tap-highlight-color:transparent}
a{color:inherit;text-decoration:none}
button,input{font:inherit;color:inherit}
button{cursor:pointer}
button:disabled{cursor:not-allowed}
:focus-visible{outline:2px solid var(--brand);outline-offset:2px}
.num{font-variant-numeric:tabular-nums}
.ico{width:18px;height:18px;flex:none}
.input{width:100%;padding:.65rem .85rem;border:1px solid var(--line);border-radius:var(--r-s);background:#fff;outline:none;font-size:16px;transition:border-color .12s,box-shadow .12s}
.input:focus{border-color:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.16)}
.input:disabled{background:var(--soft);color:var(--faint)}

/* Buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;padding:.6rem 1rem;border-radius:var(--r-s);border:1px solid var(--line);background:#fff;font-weight:600;font-size:.86rem;white-space:nowrap;min-height:40px;transition:background .12s,border-color .12s,box-shadow .12s}
.btn:hover{background:var(--soft);border-color:#d0d5dd}
.btn:disabled,.btn[aria-disabled="true"]{opacity:.45;pointer-events:none}
.btn.primary,.mbar button{background:linear-gradient(145deg,#16a34a,#166534);border-color:#15803d;color:#fff;box-shadow:0 5px 14px -8px rgba(21,128,61,.75)}
.btn.primary:hover,.mbar button:hover{background:linear-gradient(145deg,#15803d,#14532d)}
.btn.amber{color:var(--amb);background:var(--amb-s);border-color:#fedf89}
.btn.danger{color:var(--red)}
.btn.danger:hover{background:var(--red-s);border-color:#fda29b}
.btn.sm{padding:.4rem .8rem;font-size:.8rem;min-height:36px}
.btn.block{width:100%}
.btn.pay{width:100%;justify-content:space-between;padding:.9rem 1.1rem;font-size:1rem;font-weight:700;min-height:54px;border-radius:14px}
.btn.pay b{font-weight:700}

/* Shell */
.app{height:100vh;height:100dvh;display:flex;flex-direction:column;overflow:hidden}
.bar{flex:none;display:flex;align-items:center;gap:.9rem;height:60px;padding:0 1.2rem;background:rgba(255,255,255,.96);border-bottom:1px solid var(--line);box-shadow:0 4px 18px -16px rgba(2,44,34,.45);backdrop-filter:blur(12px);z-index:20}
.brand{display:flex;align-items:center;gap:.65rem;min-width:0}
.logo{width:36px;height:36px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(145deg,#16a34a,#14532d);color:#fff;font-weight:800;flex:none;box-shadow:0 8px 18px -10px rgba(21,128,61,.75)}
.brand strong{display:block;font-size:.98rem;line-height:1.15;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.brand small{display:block;font-size:.72rem;color:var(--mut)}
.sp{flex:1}
.salesbtn{display:flex;align-items:center;gap:.6rem;padding:.4rem .9rem .4rem .5rem;border:1px solid var(--line);border-radius:12px;background:#fff;text-align:left}
.salesbtn:hover{background:var(--soft)}
.salesbtn .i{width:30px;height:30px;border-radius:9px;background:var(--ok-s);color:var(--ok);display:grid;place-items:center}
.salesbtn span{display:block;font-size:.7rem;color:var(--mut);line-height:1.1}
.salesbtn strong{font-size:.9rem;line-height:1.2}
.me{display:flex;align-items:center;gap:.6rem}
.avatar{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:var(--brand-s);color:var(--brand-d);font-weight:700;font-size:.85rem;flex:none}
.who{line-height:1.2}.who strong{display:block;font-size:.84rem}.who small{display:block;font-size:.72rem;color:var(--mut);text-transform:capitalize}
.signout{padding:.4rem .8rem;border:1px solid var(--line);border-radius:var(--r-s);font-size:.8rem;font-weight:600;color:var(--mut)}
.signout:hover{background:var(--soft);color:var(--text)}

.main{flex:1 1 auto;min-height:0;display:grid;grid-template-columns:minmax(0,1fr) 410px}
.catalog{min-width:0;min-height:0;display:flex;flex-direction:column}
.right{min-height:0;display:flex;flex-direction:column;background:#fff;border-left:1px solid var(--line)}

/* Orders strip */
.strip{flex:none;display:flex;gap:.6rem;align-items:stretch;padding:.85rem 1.2rem .3rem}
.newo{flex:none;display:flex;gap:.4rem;align-items:center;background:#fff;border:1px solid var(--line);border-radius:12px;padding:.3rem .3rem .3rem .75rem;box-shadow:var(--sh)}
.newo input{border:0;outline:0;background:none;width:150px;font-size:.88rem;min-width:0}
.chips{display:flex;gap:.5rem;overflow-x:auto;scrollbar-width:none;flex:1;min-width:0;padding:2px}
.chips::-webkit-scrollbar{display:none}
.chip{flex:none;display:flex;flex-direction:column;justify-content:center;min-width:132px;padding:.45rem .8rem;border-radius:12px;background:#fff;border:1px solid var(--line);box-shadow:var(--sh)}
.chip:hover{border-color:var(--brand-l)}
.chip.on{border-color:#16a34a;background:#f0fdf4;box-shadow:0 0 0 1px #16a34a}
.chip.held{background:var(--amb-s);border-color:#fedf89}
.chip.held.on{box-shadow:0 0 0 1px var(--amb);border-color:var(--amb)}
.chip b{font-size:.84rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px}
.chip small{color:var(--mut);font-size:.74rem;white-space:nowrap}
.chip em{font-style:normal;font-weight:700;color:var(--brand-d)}
.chip.held em{color:var(--amb)}
.nochip{align-self:center;color:var(--mut);font-size:.85rem;padding-left:.3rem}

/* Toolbar + grid */
.tools{flex:none;padding:.6rem 1.2rem .4rem;display:grid;gap:.7rem}
.sr{display:flex;gap:.6rem}
.search{position:relative;flex:1;min-width:0}
.search svg{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--faint);pointer-events:none}
.search .input{padding-left:2.6rem;height:46px;border-radius:12px;box-shadow:var(--sh)}
.kbd{position:absolute;right:.8rem;top:50%;transform:translateY(-50%);font:600 .72rem var(--mono);color:var(--faint);border:1px solid var(--line);border-radius:6px;padding:.05rem .4rem;background:var(--soft);pointer-events:none}
.view{display:flex;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff;flex:none;box-shadow:var(--sh)}
.view button{width:44px;border:0;background:none;display:grid;place-items:center;color:var(--mut)}
.view button.on{background:#dcfce7;color:#166534}
.cats{display:flex;gap:.45rem;overflow-x:auto;scrollbar-width:none}
.cats::-webkit-scrollbar{display:none}
.cat{flex:none;padding:.42rem 1rem;border-radius:999px;border:1px solid var(--line);background:#fff;font-weight:600;font-size:.83rem;color:var(--mut);min-height:36px}
.cat:hover{color:var(--text);border-color:#d0d5dd}
.cat.on{background:#14532d;border-color:#14532d;color:#fff}
.tiles{flex:1 1 auto;overflow-y:auto;padding:.4rem 1.2rem 1.5rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(176px,1fr));gap:.85rem;align-content:start}
.tile-form{margin:0;min-width:0}
.tile{width:100%;height:100%;min-height:150px;text-align:left;background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:.95rem;display:flex;flex-direction:column;gap:.5rem;position:relative;box-shadow:var(--sh);transition:border-color .12s,box-shadow .15s,transform .1s}
.tile:hover{border-color:var(--brand-l);box-shadow:0 14px 28px -18px rgba(21,128,61,.55)}
.tile:active{transform:scale(.975)}
.tile:disabled{opacity:.5;transform:none;box-shadow:var(--sh)}
.t-top{display:flex;justify-content:space-between;align-items:center;gap:.4rem}
.t-cat{font-size:.7rem;font-weight:600;color:#166534;background:#dcfce7;padding:.15rem .55rem;border-radius:999px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.t-qty{min-width:24px;height:24px;padding:0 .4rem;border-radius:999px;background:#15803d;color:#fff;font-weight:700;font-size:.74rem;display:grid;place-items:center;flex:none}
.t-name{font-weight:650;font-size:.95rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1}
.t-foot{display:flex;justify-content:space-between;align-items:center;gap:.4rem}
.t-price{font-weight:750;font-size:1rem;font-variant-numeric:tabular-nums}
.t-add{width:34px;height:34px;border-radius:11px;background:#dcfce7;color:#15803d;display:grid;place-items:center;transition:background .12s,color .12s;flex:none}
.tile:hover .t-add{background:#15803d;color:#fff}
.tiles.list{grid-template-columns:1fr;gap:.5rem}
.tiles.list .tile{min-height:0;flex-direction:row;align-items:center;padding:.65rem .9rem;gap:.8rem}
.tiles.list .t-top{order:2;flex:none}.tiles.list .t-name{order:1}.tiles.list .t-foot{order:3;gap:.8rem}
.nomatch{grid-column:1/-1;text-align:center;color:var(--mut);padding:3rem 1rem}
.empty{color:var(--mut);font-size:.85rem;padding:1rem;text-align:center}

/* Ticket */
.t-head{flex:none;display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;padding:1.1rem 1.25rem .8rem}
.t-id{font-size:1.15rem;font-weight:750;letter-spacing:-.01em}
.t-sub{font-size:.8rem;color:var(--mut);margin-top:.1rem}
.t-x{display:none;width:38px;height:38px;border-radius:10px;border:1px solid var(--line);background:#fff;font-size:1.2rem;place-items:center}
.pill{font-size:.74rem;font-weight:650;padding:.22rem .65rem;border-radius:999px;text-transform:capitalize}
.pill.open{background:#dcfce7;color:#166534}
.pill.held{background:var(--amb-s);color:var(--amb)}
.pill.paid{background:var(--ok-s);color:var(--ok)}
.pill.cancelled{background:var(--red-s);color:var(--red)}
.pay-banner{margin:0 1.25rem .8rem;padding:.7rem .9rem;border-radius:12px;font-size:.82rem;font-weight:600;line-height:1.4}
.pay-banner strong{display:block;margin-bottom:.15rem}
.pay-banner.wait{background:var(--amb-s);border:1px solid #fedf89;color:var(--amb)}
.pay-banner.bad{background:var(--red-s);border:1px solid #fda29b;color:var(--red)}
.pay-banner.info{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.rename{flex:none;display:flex;gap:.5rem;padding:0 1.25rem .8rem}
.rename .input{padding:.5rem .75rem}
.lines{flex:1 1 auto;overflow-y:auto;padding:0 1.25rem;min-height:110px;border-top:1px solid var(--line)}
.line{display:flex;align-items:center;gap:.7rem;padding:.8rem 0;border-bottom:1px solid #f2f4f7}
.li{flex:1;min-width:0}
.li-name{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.li-sub{font-size:.76rem;color:var(--mut)}
.li-tot{font-weight:700;text-align:right;min-width:90px}
.stepper{display:flex;align-items:center;background:var(--soft);border:1px solid var(--line);border-radius:10px;padding:2px}
.stepper button{width:30px;height:30px;border-radius:8px;border:0;background:transparent;font-weight:700;display:grid;place-items:center;font-size:1rem}
.stepper button:hover{background:#fff;box-shadow:var(--sh)}
.stepper button:disabled{opacity:.4}
.stepper span{min-width:26px;text-align:center;font-weight:700}
.foot{flex:none;border-top:1px solid var(--line);background:var(--soft);padding:.9rem 1.25rem 1.1rem;display:grid;gap:.75rem}
.foot form{margin:0}
.sums{display:grid;gap:.25rem;font-size:.9rem}
.sums .r{display:flex;justify-content:space-between;color:var(--mut)}
.sums .neg{color:var(--red)}
.sums .tx{font-size:.78rem}
.sums .total{display:flex;justify-content:space-between;align-items:baseline;margin-top:.35rem;font-size:1.5rem;font-weight:800;color:var(--text);letter-spacing:-.02em}
.paid-box{border:1px solid var(--line);border-radius:12px;background:#fff;padding:.6rem .8rem;display:grid;gap:.25rem;font-size:.86rem}
.paid-box .r{display:flex;justify-content:space-between;color:var(--mut)}
.paid-box .bal{display:flex;justify-content:space-between;align-items:baseline;font-weight:800;font-size:1.1rem;color:var(--brand-d);border-top:1px dashed var(--line);padding-top:.35rem;margin-top:.2rem}
.disc{display:flex;gap:.5rem;align-items:center}
.disc label{font-size:.8rem;color:var(--mut);font-weight:600}
.disc .input{padding:.45rem .7rem}
.row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem}
.none{flex:1;display:grid;place-items:center;text-align:center;padding:2rem;color:var(--mut)}
.none svg{color:var(--faint);margin-bottom:.7rem}
.none strong{display:block;color:var(--text);margin-bottom:.2rem}

/* Receipt panel */
.rc-tools{flex:none;display:flex;gap:.6rem;align-items:center;padding:0 1.25rem .8rem}
.seg{display:inline-flex;border:1px solid var(--line);border-radius:var(--r-s);overflow:hidden;background:#fff}
.seg a{padding:.35rem .85rem;font-size:.8rem;font-weight:600;color:var(--mut)}
.seg a.on{background:#dcfce7;color:#166534}
.rc-note{font-size:.78rem;color:var(--mut);margin-left:auto}
.rc-stage{flex:1 1 auto;min-height:0;overflow:auto;background:var(--bg);border-top:1px solid var(--line);padding:1.2rem 1rem 1.6rem;display:flex;justify-content:center;align-items:flex-start}
.rc-foot{flex:none;display:flex;gap:.55rem;padding:.85rem 1.25rem;border-top:1px solid var(--line)}
.rc-foot .btn{flex:1}
#ticket .rc{box-shadow:0 14px 30px -18px rgba(16,24,40,.5);-webkit-mask:conic-gradient(from -45deg at bottom,#0000,#000 1deg 89deg,#0000 90deg) 50%/14px 100%;mask:conic-gradient(from -45deg at bottom,#0000,#000 1deg 89deg,#0000 90deg) 50%/14px 100%;padding-bottom:7mm}
<?= $rcCss ?>

/* Dialogs: payment + sales */
.overlay{position:fixed;inset:0;z-index:50;background:rgba(16,24,40,.55);backdrop-filter:blur(3px);overflow-y:auto;padding:1.2rem;display:flex;justify-content:center;align-items:flex-start}
.dlg{background:#fff;width:100%;max-width:440px;border-radius:20px;margin:auto;box-shadow:0 30px 60px -20px rgba(16,24,40,.5);overflow:hidden}
.overlay.side{justify-content:flex-end;padding:0}
.overlay.side .dlg{max-width:420px;height:100%;margin:0;border-radius:0;display:flex;flex-direction:column}
.dh{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1.15rem 1.3rem;border-bottom:1px solid var(--line);flex:none}
.dh h3{font-size:1.08rem;font-weight:700}
.dh p{font-size:.8rem;color:var(--mut);margin-top:.1rem}
.x{width:38px;height:38px;border-radius:10px;border:1px solid var(--line);background:#fff;font-size:1.2rem;display:grid;place-items:center;color:var(--mut);flex:none}
.pay-f{display:grid;gap:1rem;padding:1.2rem 1.3rem}
.due{display:flex;justify-content:space-between;align-items:baseline;padding:1rem 1.1rem;border-radius:14px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.due strong{font-size:1.7rem;font-weight:800;letter-spacing:-.02em;color:#14532d}
.due small{display:block;font-size:.76rem;font-weight:600;color:var(--mut)}
.segp{display:grid;grid-template-columns:1fr 1fr;gap:.55rem}
.segp label{position:relative}
.segp input{position:absolute;opacity:0;pointer-events:none}
.segp span{display:flex;align-items:center;justify-content:center;gap:.45rem;padding:.85rem;border:1.5px solid var(--line);border-radius:14px;font-weight:700;cursor:pointer;min-height:52px}
.segp input:checked+span{border-color:#16a34a;background:#f0fdf4;color:#166534}
.segp .mp input:checked+span{border-color:var(--ok);background:var(--ok-s);color:#067647}
.segp input:focus-visible+span{outline:2px solid var(--brand);outline-offset:2px}
.lbl{font-size:.82rem;font-weight:600;display:block;margin-bottom:.35rem}
.pay-f .input{font-size:1.15rem;font-weight:600;padding:.75rem .9rem}
.quick{display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem;margin-top:.55rem}
.quick button{padding:.6rem .2rem;border:1px solid var(--line);border-radius:var(--r-s);background:#fff;font-weight:600;font-size:.85rem}
.quick button:hover{background:#f0fdf4;border-color:#16a34a;color:#166534}
.chg{display:flex;justify-content:space-between;align-items:center;margin-top:.65rem;padding:.7rem 1rem;border-radius:var(--r-s);background:var(--soft);border:1px solid var(--line)}
.chg strong{font-size:1.1rem}
.chg.ok strong{color:var(--ok)}
.chg.short strong{color:var(--red)}
.hint{font-size:.78rem;color:var(--mut);margin-top:.45rem}
.check{display:flex;gap:.5rem;align-items:center;font-size:.88rem;color:var(--mut)}
.check input{width:18px;height:18px;accent-color:var(--brand)}
.pf{display:grid;grid-template-columns:1fr 2fr;gap:.55rem}
.pay-err{padding:.6rem .8rem;border-radius:10px;background:var(--red-s);color:var(--red);font-weight:600;font-size:.85rem;border:1px solid #fda29b}
.stats{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;padding:1rem 1.3rem;flex:none}
.stat{border:1px solid var(--line);border-radius:12px;padding:.6rem .7rem;background:var(--soft)}
.stat span{display:block;font-size:.72rem;color:var(--mut)}
.stat strong{font-size:.95rem}
.stat.ok strong{color:var(--ok)}
.stat.big{grid-column:1/-1;background:#dcfce7;border-color:var(--brand-l)}
.stat.big strong{font-size:1.4rem;color:var(--brand-d)}
.sl{flex:1;overflow-y:auto;padding:0 1.3rem 1.2rem}
.sl h4{font-size:.8rem;color:var(--mut);font-weight:600;padding:.4rem 0}
.sale{display:flex;justify-content:space-between;align-items:center;gap:.6rem;padding:.7rem .8rem;border:1px solid var(--line);border-radius:12px;margin-bottom:.45rem}
.sale:hover{background:var(--soft)}
.sale small{display:block;color:var(--mut);font-size:.76rem}
.sale .a{text-align:right}
.empty-msg{color:var(--mut);font-size:.85rem;padding:1rem;text-align:center}

.toast{position:fixed;left:50%;transform:translateX(-50%);bottom:1.2rem;z-index:70;padding:.75rem 1.1rem;border-radius:12px;font-weight:600;box-shadow:0 20px 40px -14px rgba(16,24,40,.5);max-width:min(440px,calc(100vw - 2rem));transition:opacity .35s}
.toast.success{background:#166534;color:#fff}
.toast.error{background:#b42318;color:#fff}
.toast.hide{opacity:0}
.mbar{display:none}

/* Responsive */
@media (min-width:768px){
  .app{min-height:100dvh}
  .main{grid-template-columns:minmax(0,1fr) minmax(340px,clamp(340px,31vw,440px))}
}
@media (min-width:1600px){.main{grid-template-columns:minmax(0,1fr) 450px}.tiles{grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem}}
@media (max-width:1180px){.main{grid-template-columns:minmax(0,1fr) 360px}.who{display:none}}
@media (max-width:1023px){
  .main{grid-template-columns:minmax(0,1fr) 340px}
  .tiles{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));padding:.4rem 1rem 1.2rem}
  .strip,.tools{padding-left:1rem;padding-right:1rem}
  .kbd{display:none}
}
@media (min-width:768px) and (max-width:900px){
  .bar{padding-inline:.85rem;gap:.55rem}
  .main{grid-template-columns:minmax(0,1fr) 320px}
  .strip,.tools{padding-left:.8rem;padding-right:.8rem}
  .tiles{grid-template-columns:repeat(auto-fill,minmax(138px,1fr));padding-inline:.8rem;gap:.6rem}
  .tile{min-height:136px;padding:.75rem}
  .newo input{width:110px}
}
@media (max-width:767px){
  .app{height:auto;min-height:100dvh;overflow:visible;padding-bottom:calc(82px + var(--safe-b))}
  .bar{position:sticky;top:0;height:56px;padding:0 .7rem;gap:.5rem}
  .brand{gap:.45rem}
  .logo{width:33px;height:33px;border-radius:10px}
  .brand strong{font-size:.9rem;max-width:42vw}
  .brand small,.signout,.salesbtn span{display:none}
  .me{gap:.35rem}
  .avatar{width:31px;height:31px}
  .salesbtn{padding:.35rem .6rem .35rem .4rem;min-height:40px}
  .main{display:block}
  .strip{padding:.7rem .7rem .2rem;flex-direction:column;gap:.5rem}
  .newo{width:100%}.newo input{flex:1;width:auto}
  .tools{padding:.5rem .7rem .3rem;position:sticky;top:56px;background:var(--bg);z-index:8}
  .tiles{overflow:visible;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem;padding:.4rem .7rem 1rem}
  .tile{min-height:132px;padding:.7rem;border-radius:14px}
  .t-name{font-size:.9rem}
  .t-price{font-size:.94rem}
  .right{position:fixed;left:0;right:0;bottom:0;height:calc(92dvh - env(safe-area-inset-top,0px));max-height:900px;z-index:40;border:0;border-radius:22px 22px 0 0;box-shadow:0 -20px 44px -14px rgba(16,24,40,.5);transform:translateY(106%);transition:transform .28s;padding-bottom:var(--safe-b)}
  body.sheet .right{transform:translateY(0)}
  body.sheet{overflow:hidden}
  body.sheet .mbar{display:none}
  .t-x{display:grid}
  .t-head{padding:.85rem 1rem .7rem}
  .rename,.lines{padding-left:1rem;padding-right:1rem}
  .foot{padding:.8rem 1rem calc(.9rem + var(--safe-b))}
  .rc-stage{padding:1rem .6rem 1.4rem}
  .rc-foot{padding:.75rem 1rem calc(.75rem + var(--safe-b))}
  .mbar{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:30;padding:.7rem 1rem calc(.7rem + var(--safe-b));background:#fff;border-top:1px solid var(--line);box-shadow:0 -10px 24px -14px rgba(16,24,40,.35);align-items:center;gap:.75rem}
  .mbar div{flex:1;line-height:1.25}
  .mbar small{display:block;color:var(--mut);font-size:.74rem}
  .mbar strong{font-size:1.1rem}
  .mbar button{padding:.7rem 1.3rem;border-radius:12px;border:0;color:#fff;font-weight:700;min-height:46px}
  .overlay{padding:0;align-items:flex-end;justify-content:center}
  .dlg,.overlay.side .dlg{border-radius:22px 22px 0 0;margin:0;max-width:none;height:auto;max-height:94dvh;overflow:auto}
  .overlay.side .dlg{height:92dvh}
  .pay-f{padding:1rem 1rem calc(1.2rem + var(--safe-b))}
  .toast{bottom:calc(90px + var(--safe-b))}
}
@media (max-width:767px) and (orientation:landscape){.right{height:100dvh;border-radius:0}.tiles{grid-template-columns:repeat(3,minmax(0,1fr))}.tools{position:static}}
@media (max-width:420px){
  .brand strong{max-width:34vw}
  .me{gap:.25rem}
  .tiles{gap:.45rem;padding-inline:.55rem}
  .tile{min-height:124px;padding:.6rem}
  .t-cat{font-size:.64rem}
  .t-add{width:30px;height:30px;border-radius:9px}
  .t-price{font-size:.88rem}
  .strip,.tools{padding-inline:.55rem}
  .row3{grid-template-columns:1fr 1fr}
  .stats{padding:.8rem;gap:.45rem}
  .stat{padding:.55rem}
  .due strong{font-size:1.4rem}
}
@media (max-width:380px){.quick{grid-template-columns:repeat(2,1fr)}.pf{grid-template-columns:1fr}.brand strong{max-width:120px}.tiles{gap:.5rem;padding:.4rem .6rem 1rem}}
@media (max-height:560px) and (orientation:landscape) and (max-width:900px){
  .bar{height:50px}
  .right{height:100dvh;max-height:none;border-radius:0}
  .tools{position:static}
  .tiles{grid-template-columns:repeat(3,minmax(0,1fr))}
  .dlg{max-height:100dvh}
}
@media (hover:none){.tile:hover{border-color:var(--line);box-shadow:var(--sh)}.btn:hover{background:inherit}}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation:none!important;transition:none!important}}

/* Print: the Print button prints only the receipt through its own frame */
@page{margin:0;size:<?= $paper ?>mm auto}
@media print{
  html,body{height:auto;background:#fff}
  .bar,.strip,.tools,.tiles,.t-head,.rc-tools,.rc-foot,.mbar,.toast,.overlay{display:none!important}
  .app,.main,.catalog,.right,.rc-stage{display:block!important;position:static!important;overflow:visible!important;height:auto!important;transform:none!important;box-shadow:none!important;padding:0!important;border:0!important;background:#fff!important}
  #ticket .rc{box-shadow:none;-webkit-mask:none;mask:none;margin:0;padding:4mm 3mm}
}
</style>
</head>
<body<?= $receiptOrd ? ' class="sheet"' : '' ?>>

<div class="app">

<header class="bar">
  <div class="brand">
    <div class="logo"><?= $h($initial) ?></div>
    <div><strong><?= $h($shop) ?></strong><small>Point of sale</small></div>
  </div>
  <div class="sp"></div>
  <button class="salesbtn" type="button" id="openSales" aria-label="Sales today">
    <span class="i"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg></span>
    <div><span>My sales today</span><strong class="num"><?= $h($money($today['sales'])) ?></strong></div>
  </button>
  <div class="me">
    <div class="avatar"><?= $h($meInit) ?></div>
    <div class="who"><strong><?= $h($me['name']) ?></strong><small><?= $h($me['role']) ?></small></div>
    <a class="signout" href="<?= $h(BASE_URL . 'logout') ?>">Sign out</a>
  </div>
</header>

<div class="main">

  <!-- CATALOG -->
  <section class="catalog" aria-label="Menu">

    <div class="strip">
      <form class="newo" method="POST" action="<?= $h(BASE_URL . 'cashier') ?>">
        <?= $csrfField ?>
        <input type="hidden" name="action" value="new">
        <input id="tableNo" name="table_no" maxlength="20" placeholder="Table or customer (F2)" aria-label="Table or customer">
        <button class="btn primary sm" type="submit">New order</button>
      </form>
      <div class="chips" aria-label="My orders">
        <?php if (!$openOrders && !$heldOrders): ?><span class="nochip">No open orders. Tap a product to start one.</span><?php endif; ?>
        <?php foreach ($openOrders as $o): $od = max(0, $o['total'] - $o['discount']); ?>
          <a class="chip<?= $selectedId === (int) $o['id'] ? ' on' : '' ?>" href="<?= $h($orderUrl((int) $o['id'])) ?>">
            <b>#<?= (int) $o['id'] ?><?= $o['table_no'] ? ' · ' . $h($o['table_no']) : '' ?></b>
            <small><?= (int) $o['item_count'] ?> items · <?= $h(ago($o['created_at'])) ?> · <em class="num"><?= $h($money($od)) ?></em></small>
          </a>
        <?php endforeach; ?>
        <?php foreach ($heldOrders as $o): $od = max(0, $o['total'] - $o['discount']); ?>
          <a class="chip held<?= $selectedId === (int) $o['id'] ? ' on' : '' ?>" href="<?= $h($orderUrl((int) $o['id'])) ?>">
            <b>On hold · #<?= (int) $o['id'] ?><?= $o['table_no'] ? ' · ' . $h($o['table_no']) : '' ?></b>
            <small><?= (int) $o['item_count'] ?> items · <?= $h(ago($o['created_at'])) ?> · <em class="num"><?= $h($money($od)) ?></em></small>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="tools">
      <div class="sr">
        <div class="search">
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          <input class="input" id="search" type="search" placeholder="Search products" autocomplete="off" aria-label="Search products">
          <span class="kbd">/</span>
        </div>
        <div class="view" role="group" aria-label="Layout">
          <button type="button" class="on" data-view="grid" aria-label="Grid view"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></button>
          <button type="button" data-view="list" aria-label="List view"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
        </div>
      </div>
      <div class="cats">
        <button type="button" class="cat on" data-cat="">All</button>
        <?php foreach ($categories as $c): ?>
          <button type="button" class="cat" data-cat="<?= $h($c) ?>"><?= $h($c) ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!$products): ?>
      <div class="empty-msg" style="padding:3rem">No products yet. Ask an administrator to add products.</div>
    <?php else: ?>
      <div class="tiles" id="tiles">
        <?php foreach ($products as $p): $qIn = $inCart[(int) $p['id']] ?? 0; ?>
          <form class="tile-form" method="POST" action="<?= $h($postUrl) ?>"
                data-name="<?= $h(mb_strtolower($p['name'])) ?>" data-cat="<?= $h($p['category']) ?>">
            <?= $csrfField ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="order_id" value="<?= $selIsOpen ? $selectedId : 0 ?>">
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <button class="tile" type="submit" aria-label="Add <?= $h($p['name']) ?>" <?= $editLocked ? 'disabled' : '' ?>>
              <span class="t-top">
                <span class="t-cat"><?= $h($p['category']) ?></span>
                <?php if ($qIn): ?><span class="t-qty">×<?= $qIn ?></span><?php endif; ?>
              </span>
              <span class="t-name"><?= $h($p['name']) ?></span>
              <span class="t-foot">
                <span class="t-price"><?= $h($money($p['price'])) ?></span>
                <span class="t-add"><svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>
              </span>
            </button>
          </form>
        <?php endforeach; ?>
        <p class="nomatch" id="noMatch" hidden>No products match your search.</p>
      </div>
    <?php endif; ?>
  </section>

  <!-- TICKET or RECEIPT -->
  <aside class="right" id="ticket" aria-label="<?= $receiptOrd ? 'Receipt' : 'Current order' ?>">

  <?php if ($receiptOrd): ?>
    <div class="t-head">
      <div>
        <div class="t-id">Receipt <?= $h($rNo) ?></div>
        <div class="t-sub num"><?= $h($money($rDue)) ?></div>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center">
        <span class="pill <?= $h($receiptOrd['status']) ?>"><?= $h($receiptOrd['status']) ?></span>
        <button class="t-x" type="button" id="ticketClose" aria-label="Close">×</button>
      </div>
    </div>
    <div class="rc-tools">
      <div class="seg" aria-label="Paper width">
        <a class="<?= $paper === '58' ? 'on' : '' ?>" href="<?= $h($rcUrl($receiptId, '58')) ?>">58 mm</a>
        <a class="<?= $paper === '80' ? 'on' : '' ?>" href="<?= $h($rcUrl($receiptId, '80')) ?>">80 mm</a>
      </div>
      <span class="rc-note">Prints only the receipt</span>
    </div>
    <div class="rc-stage" id="rcStage" data-close="<?= $h($closeUrl) ?>">
      <article class="rc p<?= $h($paper) ?>" id="rcPaper">
        <div class="c">
          <div class="shop"><?= $h($shop) ?></div>
          <?php if ($shopAddr): ?><div class="sub"><?= nl2br($h($shopAddr)) ?></div><?php endif; ?>
          <?php if ($shopTel): ?><div class="sub">Tel: <?= $h($shopTel) ?></div><?php endif; ?>
          <?php if ($shopPin): ?><div class="sub">PIN: <?= $h($shopPin) ?></div><?php endif; ?>
        </div>
        <hr class="sep">
        <div class="title"><?= $rPaid ? 'Sales Receipt' : 'Order Preview' ?></div>
        <hr class="sep">
        <div class="row"><span>Receipt No</span><span><?= $h($rNo) ?></span></div>
        <div class="row"><span>Order</span><span>#<?= $receiptId ?><?= $receiptOrd['table_no'] ? ' / ' . $h($receiptOrd['table_no']) : '' ?></span></div>
        <div class="row"><span>Date</span><span><?= date('d/m/Y H:i', strtotime($rPaid && $receiptOrd['paid_at'] ? $receiptOrd['paid_at'] : $receiptOrd['created_at'])) ?></span></div>
        <div class="row"><span>Cashier</span><span><?= $h($receiptOrd['staff'] ?? $me['name']) ?></span></div>
        <?php if (!$rPaid): ?><div class="stamp"><?= $receiptOrd['status'] === 'cancelled' ? 'CANCELLED' : 'NOT PAID' ?></div><?php endif; ?>
        <hr class="sep">
        <div class="cols"><span>ITEM</span><span>AMOUNT</span></div>
        <?php $rQty = 0; foreach ($rItems as $it): $rQty += (int) $it['qty']; ?>
          <div class="it">
            <div class="nm"><?= $h($it['name']) ?></div>
            <div class="calc"><span><?= (int) $it['qty'] ?> x <?= $h(number_format((float) $it['price'], 2)) ?></span><span><?= $h(number_format($it['price'] * $it['qty'], 2)) ?></span></div>
          </div>
        <?php endforeach; ?>
        <hr class="sep">
        <div class="row cnt"><span>Items: <?= count($rItems) ?></span><span>Qty: <?= $rQty ?></span></div>
        <div class="row"><span>Subtotal</span><span><?= $h(number_format((float) $receiptOrd['total'], 2)) ?></span></div>
        <?php if ((float) $receiptOrd['discount'] > 0): ?>
          <div class="row"><span>Discount</span><span>-<?= $h(number_format((float) $receiptOrd['discount'], 2)) ?></span></div>
        <?php endif; ?>
        <div class="grand"><span>TOTAL</span><span><?= $h($cur) ?> <?= $h(number_format($rDue, 2)) ?></span></div>
        <?php if ($taxRate > 0): ?>
          <div class="row"><span><?= $h($taxLabel) ?> <?= $h($taxPct) ?>% (incl.)</span><span><?= $h(number_format($rTax, 2)) ?></span></div>
          <div class="row"><span>Net amount</span><span><?= $h(number_format($rDue - $rTax, 2)) ?></span></div>
        <?php endif; ?>

        <?php if ($rPays): ?>
          <hr class="sep">
          <?php foreach ($rPays as $rp): ?>
            <div class="row"><span><?= $rp['payment_method'] === 'mpesa' ? 'M-PESA' : 'CASH' ?></span><span><?= $h(number_format((float) $rp['amount'], 2)) ?></span></div>
            <?php if ($rp['payment_method'] === 'mpesa' && !empty($rp['mpesa_receipt'])): ?>
              <div class="row"><span>M-Pesa Ref</span><span><?= $h($rp['mpesa_receipt']) ?></span></div>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php if ($rPaid && (float) $receiptOrd['change_due'] > 0): ?>
            <div class="row"><span>Change</span><span><?= $h(number_format((float) $receiptOrd['change_due'], 2)) ?></span></div>
          <?php endif; ?>
          <?php if (!$rPaid && $rBal > 0): ?>
            <div class="row cnt"><span>Balance due</span><span><?= $h(number_format($rBal, 2)) ?></span></div>
          <?php endif; ?>
        <?php elseif ($rPaid): ?>
          <hr class="sep">
          <div class="row"><span><?= $receiptOrd['payment_method'] === 'mpesa' ? 'M-PESA' : 'CASH' ?></span><span><?= $h(number_format($rDue, 2)) ?></span></div>
        <?php endif; ?>

        <hr class="sep">
        <div class="bc" data-bc="<?= $h($rNo) ?>"><small><?= $h($rNo) ?></small></div>
        <div class="qr" data-qr="<?= $h($rQr) ?>"></div>
        <hr class="sep">
        <div class="foot"><?= nl2br($h($footer)) ?></div>
        <div class="fine">Goods once sold are not returnable without this receipt.</div>
        <div class="fine">Printed <?= date('d/m/Y H:i') ?></div>
      </article>
    </div>
    <div class="rc-foot">
      <button class="btn primary" type="button" id="printBtn">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4v-7h16v7h-2"/><rect x="6" y="14" width="12" height="7"/></svg>
        Print receipt
      </button>
      <a class="btn" href="<?= $h($closeUrl) ?>">Done</a>
    </div>

  <?php elseif (!$selected): ?>
    <div class="none">
      <div>
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg>
        <strong>No order selected</strong>
        <p>Tap a product to start an order, or pick one from the list above.</p>
      </div>
    </div>

  <?php else: ?>
    <div class="t-head">
      <div>
        <div class="t-id">Order #<?= (int) $selected['id'] ?></div>
        <div class="t-sub"><?= $selected['table_no'] ? 'Table ' . $h($selected['table_no']) . ' · ' : '' ?><?= date('H:i', strtotime($selected['created_at'])) ?></div>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center">
        <span class="pill <?= $h($selected['status']) ?>"><?= $h($selected['status']) ?></span>
        <button class="t-x" type="button" id="ticketClose" aria-label="Close">×</button>
      </div>
    </div>

    <?php if ($banner): ?>
      <div id="payWait" class="pay-banner <?= $h($banner[0]) ?>">
        <strong><?= $h($banner[1]) ?></strong>
        <?= $h($banner[2]) ?>
      </div>
    <?php endif; ?>

    <?php if ($selIsOpen): ?>
      <form class="rename" method="POST" action="<?= $h($postUrl) ?>">
        <?= $csrfField ?>
        <input type="hidden" name="order_id" value="<?= (int) $selected['id'] ?>">
        <input type="hidden" name="action" value="rename">
        <input class="input" name="table_no" maxlength="20" value="<?= $h($selected['table_no'] ?? '') ?>" placeholder="Table or customer name" aria-label="Table or customer name" <?= $editLocked ? 'disabled' : '' ?>>
        <button class="btn sm" type="submit" <?= $editLocked ? 'disabled' : '' ?>>Save</button>
      </form>
    <?php endif; ?>

    <div class="lines">
      <?php if (!$items): ?><p class="empty-msg" style="padding:2.2rem 0">No items yet. Tap a product to add it.</p><?php endif; ?>
      <?php foreach ($items as $it): ?>
        <div class="line">
          <div class="li">
            <div class="li-name"><?= $h($it['name']) ?></div>
            <div class="li-sub num"><?= $h($money($it['price'])) ?> each</div>
          </div>
          <?php if ($selIsOpen): ?>
            <form class="stepper" method="POST" action="<?= $h($postUrl) ?>">
              <?= $csrfField ?>
              <input type="hidden" name="order_id" value="<?= (int) $selected['id'] ?>">
              <input type="hidden" name="action" value="qty">
              <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
              <button name="qty" value="<?= (int) $it['qty'] - 1 ?>" aria-label="<?= (int) $it['qty'] === 1 ? 'Remove item' : 'Decrease' ?>" <?= $editLocked ? 'disabled' : '' ?>><?= (int) $it['qty'] === 1 ? '✕' : '−' ?></button>
              <span class="num"><?= (int) $it['qty'] ?></span>
              <button name="qty" value="<?= (int) $it['qty'] + 1 ?>" aria-label="Increase" <?= $editLocked ? 'disabled' : '' ?>>+</button>
            </form>
          <?php else: ?>
            <span class="num" style="color:var(--mut);font-weight:600">× <?= (int) $it['qty'] ?></span>
          <?php endif; ?>
          <div class="li-tot num"><?= $h($money($it['price'] * $it['qty'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="foot">
      <div class="sums num">
        <div class="r"><span>Subtotal</span><span><?= $h($money($selected['total'])) ?></span></div>
        <?php if ((float) $selected['discount'] > 0): ?>
          <div class="r neg"><span>Discount</span><span>−<?= $h($money($selected['discount'])) ?></span></div>
        <?php endif; ?>
        <?php if ($taxRate > 0): ?>
          <div class="r tx"><span><?= $h($taxLabel) ?> <?= $h($taxPct) ?>% included</span><span><?= $h($money($selTax)) ?></span></div>
        <?php endif; ?>
        <div class="total"><span>Total</span><span><?= $h($money($selDue)) ?></span></div>
      </div>

      <?php if ($selPaid > 0 && $selIsOpen): ?>
        <div class="paid-box num">
          <?php if ($sum['cash'] > 0): ?><div class="r"><span>Paid in cash</span><span><?= $h($money($sum['cash'])) ?></span></div><?php endif; ?>
          <?php if ($sum['mpesa'] > 0): ?><div class="r"><span>Paid by M-Pesa</span><span><?= $h($money($sum['mpesa'])) ?></span></div><?php endif; ?>
          <div class="bal"><span>Balance due</span><span><?= $h($money($selRemaining)) ?></span></div>
        </div>
      <?php endif; ?>

      <?php if ($selIsOpen): ?>
        <form class="disc" method="POST" action="<?= $h($postUrl) ?>">
          <?= $csrfField ?>
          <input type="hidden" name="order_id" value="<?= (int) $selected['id'] ?>">
          <input type="hidden" name="action" value="discount">
          <label for="discAmt">Discount</label>
          <input class="input" id="discAmt" type="number" name="discount" min="0" step="0.01" inputmode="decimal"
                 value="<?= number_format((float) $selected['discount'], 2, '.', '') ?>" <?= $editLocked ? 'disabled' : '' ?>>
          <button class="btn sm" type="submit" <?= $editLocked ? 'disabled' : '' ?>>Apply</button>
        </form>

        <button class="btn primary pay" type="button" id="openPay" <?= ($items && $selRemaining > 0 && !$selPending) ? '' : 'disabled' ?>>
          <span>Take payment</span><b class="num"><?= $h($money($selRemaining)) ?></b>
        </button>
        <div class="row3">
          <a class="btn sm" href="<?= $h($rcUrl((int) $selected['id'])) ?>" <?= $items ? '' : 'aria-disabled="true"' ?>>Preview</a>
          <form method="POST" action="<?= $h($postUrl) ?>">
            <?= $csrfField ?>
            <input type="hidden" name="order_id" value="<?= (int) $selected['id'] ?>">
            <input type="hidden" name="action" value="hold">
            <button class="btn sm amber block" type="submit" <?= ($items && !$editLocked) ? '' : 'disabled' ?>>Hold</button>
          </form>
          <form method="POST" action="<?= $h($postUrl) ?>" data-confirm="Cancel this order? This cannot be undone.">
            <?= $csrfField ?>
            <input type="hidden" name="order_id" value="<?= (int) $selected['id'] ?>">
            <input type="hidden" name="action" value="cancel">
            <button class="btn sm danger block" type="submit" <?= $editLocked ? 'disabled' : '' ?>>Cancel</button>
          </form>
        </div>

      <?php elseif ($selHeld): ?>
        <form method="POST" action="<?= $h($postUrl) ?>">
          <?= $csrfField ?>
          <input type="hidden" name="order_id" value="<?= (int) $selected['id'] ?>">
          <input type="hidden" name="action" value="resume">
          <button class="btn primary block" type="submit">Resume order</button>
        </form>
        <form method="POST" action="<?= $h($postUrl) ?>" data-confirm="Cancel this order? This cannot be undone.">
          <?= $csrfField ?>
          <input type="hidden" name="order_id" value="<?= (int) $selected['id'] ?>">
          <input type="hidden" name="action" value="cancel">
          <button class="btn sm danger block" type="submit">Cancel order</button>
        </form>

      <?php else: ?>
        <a class="btn primary block" href="<?= $h($rcUrl((int) $selected['id'])) ?>">View receipt</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  </aside>
</div>
</div>

<!-- Mobile bottom bar -->
<div class="mbar">
  <div>
    <small><?= $receiptOrd ? 'Receipt ' . $h($rNo) : ($selected ? 'Order #' . (int) $selected['id'] : 'No order') ?></small>
    <strong class="num"><?= $h($money($receiptOrd ? $rDue : ($selRemaining ?: $selDue))) ?></strong>
  </div>
  <button type="button" id="mbarBtn"><?= $receiptOrd ? 'View receipt' : ($selected ? 'View order' : 'Open') ?></button>
</div>

<!-- SALES (today + recent) -->
<div class="overlay side" id="salesModal" hidden>
  <div class="dlg" role="dialog" aria-modal="true" aria-label="My sales">
    <div class="dh">
      <div><h3>My sales</h3><p>Today, <?= date('d M Y') ?></p></div>
      <button class="x" type="button" data-close aria-label="Close">×</button>
    </div>
    <div class="stats num">
      <div class="stat big"><span>Total sales · <?= (int) $today['n'] ?> orders</span><strong><?= $h($money($today['sales'])) ?></strong></div>
      <div class="stat"><span>Cash</span><strong><?= $h($money($today['cash'])) ?></strong></div>
      <div class="stat ok"><span>M-Pesa</span><strong><?= $h($money($today['mpesa'])) ?></strong></div>
      <div class="stat"><span>Open / held</span><strong><?= count($openOrders) ?> / <?= count($heldOrders) ?></strong></div>
    </div>
    <div class="sl">
      <h4>Recent sales</h4>
      <?php if (!$recentPaid): ?><p class="empty-msg">No paid orders yet.</p><?php endif; ?>
      <?php foreach ($recentPaid as $rp):
          $ml = $rp['payment_method'] === 'mpesa' ? 'M-Pesa' : ($rp['payment_method'] === 'split' ? 'Cash + M-Pesa' : 'Cash'); ?>
        <a class="sale" href="<?= $h($rcUrl((int) $rp['id'])) ?>">
          <div><strong>#<?= (int) $rp['id'] ?></strong><small><?= $h($ml) ?> · <?= date('H:i', strtotime($rp['paid_at'])) ?></small></div>
          <div class="a"><strong class="num"><?= $h($money($rp['total'] - $rp['discount'])) ?></strong><small style="color:var(--brand)">View receipt</small></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($selIsOpen && $items && $selRemaining > 0 && !$selPending): ?>
<!-- PAYMENT -->
<div class="overlay" id="payModal" hidden>
  <div class="dlg" role="dialog" aria-modal="true" aria-label="Take payment">
    <div class="dh">
      <div>
        <h3>Take payment</h3>
        <p>Order #<?= (int) $selected['id'] ?><?= $selected['table_no'] ? ' · ' . $h($selected['table_no']) : '' ?></p>
      </div>
      <button class="x" type="button" data-close aria-label="Close">×</button>
    </div>

    <form id="payForm" class="pay-f" method="POST" action="<?= $h($postUrl) ?>"
          data-remaining="<?= number_format($selRemaining, 2, '.', '') ?>" data-cur="<?= $h($cur) ?>">
      <?= $csrfField ?>
      <input type="hidden" name="order_id" value="<?= (int) $selected['id'] ?>">
      <input type="hidden" name="action" value="pay">

      <div class="due">
        <div><small>Balance due</small><strong class="num"><?= $h($money($selRemaining)) ?></strong></div>
        <small class="num">of <?= $h($money($selDue)) ?></small>
      </div>

      <div class="segp">
        <label><input type="radio" name="method" value="cash" checked><span>Cash</span></label>
        <label class="mp"><input type="radio" name="method" value="mpesa"><span>M-Pesa</span></label>
      </div>

      <div data-pane="cash">
        <label class="lbl" for="cashTendered">Cash received from customer</label>
        <input class="input" id="cashTendered" name="cash_tendered" type="number" min="0" step="0.01" inputmode="decimal"
               value="<?= number_format($selRemaining, 2, '.', '') ?>">
        <label class="lbl" for="cashApplied" style="margin-top:.6rem">Apply to this order</label>
        <input class="input" id="cashApplied" name="cash_applied" type="number" min="0" step="0.01" inputmode="decimal"
               value="<?= number_format($selRemaining, 2, '.', '') ?>">
        <div class="quick">
          <?php foreach ($quick as $amt): ?>
            <button type="button" class="quick-btn" data-amt="<?= number_format($amt, 2, '.', '') ?>">
              <?= abs($amt - $selRemaining) < 0.005 ? 'Exact' : $h(number_format($amt, 0)) ?>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="chg ok num" id="chgBox"><span id="chgLbl">Change due</span><strong id="chgAmt"><?= $h($money(0)) ?></strong></div>
      </div>

      <div data-pane="mpesa" hidden>
        <label class="lbl" for="phone_number">Customer M-Pesa number</label>
        <input class="input" id="phone_number" name="phone_number" type="tel" inputmode="tel"
               autocomplete="tel" placeholder="0712345678" maxlength="16">
        <label class="lbl" for="mpesaAmount" style="margin-top:.6rem">Amount to charge (whole shillings)</label>
        <input class="input" id="mpesaAmount" name="mpesa_amount" type="number" min="1" step="1" inputmode="numeric"
               value="<?= (int) floor($selRemaining) ?>">
        <p class="hint">The customer gets a prompt on their phone. The order updates when they enter their PIN.</p>
      </div>

      <div class="pay-err" id="payErr" role="alert" hidden></div>

      <label class="check"><input type="checkbox" name="print" value="1" checked> Print receipt when fully paid</label>

      <div class="pf">
        <button class="btn" type="button" data-close>Cancel</button>
        <button class="btn primary" type="submit" id="paySubmit">Confirm payment</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($flash): ?>
  <div class="toast <?= $h($flash['type']) ?>" id="toast" role="status"><?= $h($flash['msg']) ?></div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
(function () {
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const HAS_RECEIPT = <?= $receiptOrd ? 'true' : 'false' ?>;
  const AUTO_PRINT  = <?= ($autoPrint || !empty($_GET['print'])) ? 'true' : 'false' ?>;

  const toast = $('#toast');
  if (toast) setTimeout(() => toast.classList.add('hide'), 6000);

  /* Keep the selected order chip in view */
  const on = $('.chip.on'); if (on && on.scrollIntoView) on.scrollIntoView({ inline: 'center', block: 'nearest' });

  /* Product search + category filter + grid/list */
  const search = $('#search'), tiles = $$('.tile-form'), noMatch = $('#noMatch'), grid = $('#tiles');
  let cat = '';
  function filter() {
    const q = (search ? search.value : '').trim().toLowerCase();
    let shown = 0;
    tiles.forEach(t => {
      const ok = (!cat || t.dataset.cat === cat) && (!q || t.dataset.name.includes(q));
      t.hidden = !ok; if (ok) shown++;
    });
    if (noMatch) noMatch.hidden = shown > 0;
  }
  if (search) search.addEventListener('input', filter);
  $$('.cat').forEach(b => b.addEventListener('click', () => {
    cat = b.dataset.cat;
    $$('.cat').forEach(x => x.classList.toggle('on', x === b));
    filter();
  }));
  function setView(v) {
    if (grid) grid.classList.toggle('list', v === 'list');
    $$('.view button').forEach(b => b.classList.toggle('on', b.dataset.view === v));
    try { localStorage.setItem('pos_view', v); } catch (e) {}
  }
  $$('.view button').forEach(b => b.addEventListener('click', () => setView(b.dataset.view)));
  try { setView(localStorage.getItem('pos_view') || 'grid'); } catch (e) {}

  /* Mobile ticket sheet */
  const mbarBtn = $('#mbarBtn'), ticketClose = $('#ticketClose');
  if (mbarBtn) mbarBtn.addEventListener('click', () => document.body.classList.toggle('sheet'));
  if (ticketClose) ticketClose.addEventListener('click', () => document.body.classList.remove('sheet'));

  /* Dialogs: sales + payment */
  const pay = $('#payModal'), openPay = $('#openPay'), sales = $('#salesModal'), openSales = $('#openSales');
  const showPay  = () => { if (pay) { pay.hidden = false; setTimeout(() => { const t = $('#cashTendered'); t && t.focus(); }, 50); } };
  const hidePay  = () => { if (pay) pay.hidden = true; };
  const showSales = () => { if (sales) sales.hidden = false; };
  const hideSales = () => { if (sales) sales.hidden = true; };
  if (openPay) openPay.addEventListener('click', showPay);
  if (openSales) openSales.addEventListener('click', showSales);
  [pay, sales].forEach(m => m && m.addEventListener('click', e => {
    if (e.target === m || e.target.closest('[data-close]')) m.hidden = true;
  }));

  /* Payment form: cash, M-Pesa, or a mix (take cash, then the balance by M-Pesa) */
  const form = $('#payForm');
  if (form) {
    const R    = parseFloat(form.dataset.remaining);
    const CUR  = form.dataset.cur;
    const fmt  = n => CUR + ' ' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const tend = $('#cashTendered'), appl = $('#cashApplied'), chgBox = $('#chgBox'), chgLbl = $('#chgLbl'), chgAmt = $('#chgAmt');
    const mAmt = $('#mpesaAmount'), phone = $('#phone_number'), errBox = $('#payErr'), submit = $('#paySubmit');
    let applTouched = false;

    const method = () => (form.querySelector('input[name="method"]:checked') || {}).value || 'cash';
    const showErr = msg => { errBox.textContent = msg; errBox.hidden = false; };
    const clearErr = () => { errBox.hidden = true; errBox.textContent = ''; };

    function cashState() {
      const t = parseFloat(tend.value) || 0;
      if (!applTouched) appl.value = Math.min(t, R).toFixed(2);
      const a = parseFloat(appl.value) || 0;

      if (a >= R - 0.005) {
        const c = Math.max(0, t - a);
        chgLbl.textContent = 'Change due';
        chgAmt.textContent = fmt(c);
        chgBox.className = 'chg num ok';
      } else {
        chgLbl.textContent = 'Balance after cash';
        chgAmt.textContent = fmt(Math.max(0, R - a));
        chgBox.className = 'chg num ' + (t > a + 0.005 ? 'short' : '');
      }
    }

    function panes(focus) {
      const m = method();
      $$('[data-pane]', form).forEach(p => p.hidden = p.dataset.pane !== m);
      clearErr();
      if (focus) (m === 'cash' ? tend : phone).focus();
    }

    tend.addEventListener('input', () => { cashState(); clearErr(); });
    appl.addEventListener('input', () => { applTouched = true; cashState(); clearErr(); });
    $$('.quick-btn', form).forEach(b => b.addEventListener('click', () => {
      tend.value = Number(b.dataset.amt).toFixed(2);
      applTouched = false;
      cashState();
      tend.focus();
    }));
    $$('input[name="method"]', form).forEach(r => r.addEventListener('change', () => panes(true)));

    form.addEventListener('submit', e => {
      clearErr();
      const m = method();

      if (m === 'cash') {
        const t = parseFloat(tend.value) || 0;
        const a = parseFloat(appl.value) || 0;
        if (!(a > 0))                       { e.preventDefault(); return showErr('Enter the cash amount.'); }
        if (a > R + 0.005)                  { e.preventDefault(); return showErr('Cash is more than the balance due.'); }
        if (t + 0.005 < a)                  { e.preventDefault(); return showErr('Cash received is less than the amount applied.'); }
        if (a < R - 0.005 && t > a + 0.005) { e.preventDefault(); return showErr('Change is only given when cash covers the full balance.'); }
        if (!confirm('Record cash of ' + fmt(a) + '?')) { e.preventDefault(); return; }
      } else if (m === 'mpesa') {
        const raw = phone.value.trim();
        const normalized = raw.replace(/[\s\-()+]/g, '');
        const amount = parseFloat(mAmt.value);

        if (!/^(?:0[17]\d{8}|254[17]\d{8}|[17]\d{8})$/.test(normalized)) {
          e.preventDefault(); phone.focus(); return showErr('Enter a valid Kenyan M-Pesa number.');
        }
        if (!Number.isInteger(amount) || amount < 1) {
          e.preventDefault(); mAmt.focus(); return showErr('M-Pesa amounts must be whole shillings.');
        }
        if (amount > R + 0.005) {
          e.preventDefault(); mAmt.focus(); return showErr('M-Pesa amount is more than the balance due.');
        }
        if (!confirm('Send an M-Pesa prompt to ' + raw + ' for ' + fmt(amount) + '?')) { e.preventDefault(); return; }
      } else {
        e.preventDefault(); return showErr('Choose cash or M-Pesa.');
      }

      submit.disabled = true;
    });

    panes(false); cashState();
  }

  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

  /* Barcode (Code 39) of the receipt number */
  const C39 = {'0':'nnnwwnwnn','1':'wnnwnnnnw','2':'nnwwnnnnw','3':'wnwwnnnnn','4':'nnnwwnnnw','5':'wnnwwnnnn','6':'nnwwwnnnn','7':'nnnwnnwnw','8':'wnnwnnwnn','9':'nnwwnnwnn','A':'wnnnnwnnw','B':'nnwnnwnnw','C':'wnwnnwnnn','D':'nnnnwwnnw','E':'wnnnwwnnn','F':'nnwnwwnnn','G':'nnnnnwwnw','H':'wnnnnwwnn','I':'nnwnnwwnn','J':'nnnnwwwnn','K':'wnnnnnnww','L':'nnwnnnnww','M':'wnwnnnnwn','N':'nnnnwnnww','O':'wnnnwnnwn','P':'nnwnwnnwn','Q':'nnnnnnwww','R':'wnnnnnwwn','S':'nnwnnnwwn','T':'nnnnwnwwn','U':'wwnnnnnnw','V':'nwwnnnnnw','W':'wwwnnnnnn','X':'nwnnwnnnw','Y':'wwnnwnnnn','Z':'nwwnwnnnn','-':'nwnnnnwnw','*':'nwnnwnwnn'};
  function code39(txt) {
    let x = 0, r = '';
    for (const ch of '*' + txt.toUpperCase() + '*') {
      const p = C39[ch]; if (!p) continue;
      for (let i = 0; i < 9; i++) { const w = p[i] === 'w' ? 3 : 1; if (i % 2 === 0) r += '<rect x="' + x + '" y="0" width="' + w + '" height="40"/>'; x += w; }
      x += 1;
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + x + ' 40" preserveAspectRatio="none" fill="#000">' + r + '</svg>';
  }
  $$('[data-bc]').forEach(el => { try { el.insertAdjacentHTML('afterbegin', code39(el.dataset.bc)); } catch (e) {} });

  /* QR code inside the receipt */
  $$('[data-qr]').forEach(el => {
    try {
      if (typeof qrcode !== 'function') { el.hidden = true; return; }
      const q = qrcode(0, 'M'); q.addData(el.dataset.qr); q.make();
      el.innerHTML = q.createSvgTag({ cellSize: 3, margin: 0 });
    } catch (e) { el.hidden = true; }
  });

  /* Print: a hidden frame holds ONLY the receipt, so the dashboard can never be printed */
  const RC_CSS = <?= json_encode($rcCss) ?>;
  const PAPER  = <?= json_encode($paper) ?>;
  function doPrint() {
    const src = $('#rcPaper'); if (!src) return;
    const old = $('#printFrame'); if (old) old.remove();
    const f = document.createElement('iframe');
    f.id = 'printFrame'; f.setAttribute('aria-hidden', 'true'); f.title = 'Receipt';
    f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
    document.body.appendChild(f);
    const d = f.contentDocument;
    d.open();
    d.write('<!doctype html><html><head><meta charset="utf-8"><title>Receipt</title><style>' + RC_CSS +
      '@page{margin:0;size:' + PAPER + 'mm auto}html,body{margin:0;padding:0;background:#fff}' +
      '.rc{margin:0;box-shadow:none;max-width:none;-webkit-print-color-adjust:exact;print-color-adjust:exact}</style></head><body>' +
      src.outerHTML + '</body></html>');
    d.close();
    setTimeout(() => { try { f.contentWindow.focus(); f.contentWindow.print(); } catch (e) { window.print(); } }, 250);
  }
  const printBtn = $('#printBtn');
  if (printBtn) printBtn.addEventListener('click', doPrint);
  if (AUTO_PRINT && HAS_RECEIPT) window.addEventListener('load', () => setTimeout(doPrint, 400));

  const params = new URLSearchParams(location.search);
  if (params.has('print')) {
    params.delete('print');
    const qs = params.toString();
    history.replaceState({}, '', location.pathname + (qs ? '?' + qs : ''));
  }

  /* Keyboard: / search, F2 new order, F8 sales, F9 pay, P print, Esc close */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      if (pay && !pay.hidden) return hidePay();
      if (sales && !sales.hidden) return hideSales();
      const rc = $('#rcStage'); if (rc) { location.href = rc.dataset.close; return; }
      document.body.classList.remove('sheet');
      return;
    }
    if (/INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName || '')) return;
    if (e.key === '/') { e.preventDefault(); search && search.focus(); }
    else if (e.key === 'F2') { e.preventDefault(); const t = $('#tableNo'); t && t.focus(); }
    else if (e.key === 'F8') { e.preventDefault(); showSales(); }
    else if (e.key === 'F9') { e.preventDefault(); if (openPay && !openPay.disabled) showPay(); }
    else if ((e.key === 'p' || e.key === 'P') && HAS_RECEIPT && !e.ctrlKey && !e.metaKey) { e.preventDefault(); doPrint(); }
  });
})();
</script>

<?php if ($selPending): ?>
<!-- Waits for the M-Pesa result, then moves on to the receipt or the updated balance -->
<script>
(function () {
  const orderId  = <?= (int) $selectedId ?>;
  const base     = <?= json_encode(BASE_URL) ?>;
  const maxTries = 100; // about 5 minutes at 3-second intervals
  let tries = 0;

  const timer = setInterval(async () => {
    tries++;
    try {
      const r = await fetch(base + 'cashier/payment-status?order=' + orderId + '&_=' + Date.now(), {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json', 'ngrok-skip-browser-warning': '1' }
      });
      const type = r.headers.get('content-type') || '';
      if (r.ok && type.includes('application/json')) {
        const s = await r.json();
        if (s.done) {
          clearInterval(timer);
          location.replace(base + 'cashier?order=' + orderId +
            (s.order_status === 'paid' ? '&receipt=' + orderId : ''));
          return;
        }
      }
    } catch (e) { /* network blip: try again on the next tick */ }

    if (tries >= maxTries) {
      clearInterval(timer);
      const box = document.getElementById('payWait');
      if (box) box.innerHTML = '<strong>Still waiting for the customer</strong>Reload this page in a moment to check again.';
    }
  }, 3000);
})();
</script>
<?php endif; ?>
</body>
</html>