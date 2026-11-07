<?php
namespace App\Core;

class Route
{
    public static array $routes = [];

    public static function get(string $path, array $action, array $middleware = []): void
    {
        self::add('GET', $path, $action, $middleware);
    }

    public static function post(string $path, array $action, array $middleware = []): void
    {
        self::add('POST', $path, $action, $middleware);
    }

    private static function add(string $method, string $path, array $action, array $middleware): void
    {
        self::$routes[] = compact('method', 'path', 'action', 'middleware');
    }
}