<?php
/*
 * HTTP entry points for PayHero:
 *   callback() : PayHero posts results here (public, token-protected)
 *   status()   : the cashier polls this (requires a signed-in user who owns the order)
 */

namespace App\Controllers;

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/PaymentService.php';

final class PayHeroController
{
    public function callback(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');

        $config   = require __DIR__ . '/../includes/payhero.php';
        $expected = (string) ($config['callback_token'] ?? '');
        $given    = (string) ($_GET['token'] ?? '');

        if ($expected === '' || !hash_equals($expected, $given)) {
            \PaymentService::log('callback rejected: bad token');
            http_response_code(403);
            echo json_encode(['ok' => false]);
            return;
        }

        $raw     = (string) file_get_contents('php://input');
        \PaymentService::log('callback received: ' . substr($raw, 0, 500));

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            http_response_code(400);
            echo json_encode(['ok' => false]);
            return;
        }

        // Some PayHero payloads wrap the data in "response". VERIFY.
        $payload = is_array($decoded['response'] ?? null) ? $decoded['response'] : $decoded;

        try {
            $service = new \PaymentService(db(), $config);
            $result  = $service->handleCallback($payload);
            echo json_encode(['ok' => true, 'result' => $result['message']]);
        } catch (\Throwable $e) {
            \PaymentService::log('callback error: ' . $e->getMessage());
            http_response_code(500);   // PayHero may retry. Safe, because apply() is idempotent.
            echo json_encode(['ok' => false]);
        }
    }

    public function status(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');

        $userId  = (int) ($_SESSION['user']['id'] ?? 0);
        $orderId = (int) ($_GET['order'] ?? 0);

        if ($userId < 1) {
            http_response_code(401);
            echo json_encode(['error' => 'Not signed in']);
            return;
        }

        $pdo = db();

        $o = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? AND user_id = ?');
        $o->execute([$orderId, $userId]);
        $order = $o->fetch();

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }

        $p = $pdo->prepare(
            'SELECT status, response_message, mpesa_receipt
             FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1'
        );
        $p->execute([$orderId]);
        $pay = $p->fetch() ?: null;

        $paymentFinal = $pay && in_array($pay['status'], ['success', 'failed', 'review'], true);

        echo json_encode([
            'order_status'   => $order['status'],
            'payment_status' => $pay['status'] ?? null,
            'message'        => $pay['response_message'] ?? null,
            'receipt'        => $pay['mpesa_receipt'] ?? null,
            'done'           => $order['status'] !== 'open' || $paymentFinal,
        ]);
    }
}