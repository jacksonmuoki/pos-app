<?php
/*
 * Stock rules. Every change goes through move(), which writes the movement log.
 * Callers that change several products must run inside a transaction.
 * A product with stock_qty NULL is not tracked and is never deducted.
 */

final class Inventory
{
    /** Locks the product row and returns its stock. Null means not tracked. */
    public static function lockStock(PDO $pdo, int $productId): ?float
    {
        $s = $pdo->prepare('SELECT stock_qty FROM products WHERE id = ? FOR UPDATE');
        $s->execute([$productId]);
        $row = $s->fetch();
        if ($row === false) {
            throw new RuntimeException('That product no longer exists.');
        }
        return $row['stock_qty'] === null ? null : (float) $row['stock_qty'];
    }

    /**
     * Changes stock by $change (negative to reduce) and logs it.
     * A product that is not tracked starts from 0.
     */
    public static function move(
        PDO $pdo,
        int $productId,
        float $change,
        string $type,
        ?int $userId = null,
        string $note = '',
        ?int $orderId = null
    ): float {
        $current = self::lockStock($pdo, $productId);
        $after   = round(($current ?? 0.0) + $change, 3);

        $pdo->prepare('UPDATE products SET stock_qty = ? WHERE id = ?')->execute([$after, $productId]);

        $pdo->prepare(
            'INSERT INTO inventory_movements
                (product_id, order_id, user_id, type, qty_change, qty_after, note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            $productId,
            $orderId,
            $userId,
            $type,
            round($change, 3),
            $after,
            $note !== '' ? mb_substr($note, 0, 255) : null,
        ]);

        return $after;
    }

    /**
     * Deducts every tracked product in a paid order. Called once per order from
     * PaymentService::settleOrder(), which only runs on the open-to-paid transition.
     * Stock may go negative when a sale happens before a delivery is recorded. The
     * page shows those products so they can be counted.
     */
    public static function deductForOrder(PDO $pdo, int $orderId): void
    {
        $s = $pdo->prepare(
            'SELECT oi.product_id, oi.qty
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ? AND p.stock_qty IS NOT NULL'
        );
        $s->execute([$orderId]);

        foreach ($s->fetchAll() as $line) {
            self::move(
                $pdo,
                (int) $line['product_id'],
                -1 * (float) $line['qty'],
                'sale',
                null,
                'Sold on order #' . $orderId,
                $orderId
            );
        }
    }
}