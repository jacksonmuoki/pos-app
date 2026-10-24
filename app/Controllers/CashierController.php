<?php
namespace App\Controllers;

class CashierController extends Controller
{
    private const PAGES = [
        'dashboard', 'pos', 'sales', 'receipts', 'products', 'shifts',
    ];

    public function dashboard(): void
    {
        $this->legacy('cashier', self::PAGES);
    }

    public function createOrder(): void
    {
        $stmt = db()->prepare("INSERT INTO orders (user_id, status, created_at) VALUES (?, 'open', NOW())");
        $stmt->execute([$_SESSION['user']['id']]);

        $this->redirect('cashier');
    }
}