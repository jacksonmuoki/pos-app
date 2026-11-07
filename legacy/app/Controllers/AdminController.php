<?php
namespace App\Controllers;

class AdminController extends Controller
{
    private const PAGES = [
        'dashboard', 'products', 'categories', 'inventory',
        'sales', 'users', 'reports', 'settings',
    ];

    public function dashboard(): void
    {
        $this->legacy('admin', self::PAGES);
    }

        public function inventory(): void
    {
        require APP_ROOT . '/app/Legacy/admin/inventory.php';
    }

    public function reports(): void
    {
        require APP_ROOT . '/app/Legacy/admin/reports.php';
    }
    public function sales(): void
    {
        require APP_ROOT . '/app/Legacy/admin/sales.php';
    }
    public function users(): void
{
    require APP_ROOT . '/app/Legacy/admin/users.php';
}
}