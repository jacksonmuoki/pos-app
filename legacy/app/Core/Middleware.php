<?php
namespace App\Core;

class Middleware
{
    public static function role(array $roles): \Closure
    {
        return function () use ($roles) {
            if (empty($_SESSION['user'])) {
                header('Location: ' . BASE_URL . 'login');
                exit;
            }
            if (!in_array($_SESSION['user']['role'], $roles, true)) {
                http_response_code(403);
                exit('Forbidden');
            }
        };
    }
}