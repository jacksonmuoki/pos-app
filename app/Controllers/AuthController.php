<?php
namespace App\Controllers;

class AuthController extends Controller
{
    public function showLogin(): void
{
    $error = $_SESSION['error'] ?? null;
    unset($_SESSION['error']);

    $this->view('login', [
        'error'    => $error,
        'shopName' => setting('shop_name', 'POS'),
    ]);
}

    public function login(): void
    {
        $pin = trim($_POST['pin'] ?? '');

        if (!preg_match('/^\d{4,6}$/', $pin)) {
            $this->fail('Invalid PIN format.');
        }

        $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
        if ($_SESSION['attempts'] > 5) {
            $this->fail('Too many attempts. Try again later.');
        }

        foreach (db()->query('SELECT id, name, role, pin_hash FROM users WHERE active = 1')->fetchAll() as $user) {
            if (password_verify($pin, $user['pin_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'   => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                ];
                $_SESSION['attempts'] = 0;

                $this->redirect($user['role'] === 'admin' ? 'admin' : 'cashier');
            }
        }

        $this->fail('Incorrect PIN.');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('login');
    }

    private function fail(string $message): void
    {
        $_SESSION['error'] = $message;
        $this->redirect('login');
    }
}