<?php
namespace App\Controllers;

abstract class Controller
{
    protected function view(string $name, array $data = []): void
    {
        extract($data);
        require APP_ROOT . '/app/shared/views/' . $name . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }

    /**
     * Load a legacy page from app/Legacy/{area}/{page}.php.
     * Only pages in $allowed can be loaded.
     */
    protected function legacy(string $area, array $allowed): void
    {
        $page = $_GET['page'] ?? 'dashboard';

        if (!in_array($page, $allowed, true)) {
            http_response_code(404);
            exit('Page not found');
        }

        require APP_ROOT . '/app/Legacy/' . $area . '/' . $page . '.php';
    }
}