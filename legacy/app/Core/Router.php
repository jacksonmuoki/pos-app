<?php
namespace App\Core;

class Router
{
    public static function dispatch(string $method, string $uri): void
    {
        $base = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?: '', '/');
        $path = '/' . trim(substr($uri, strlen($base)), '/');

        foreach (Route::$routes as $route) {
            if ($route['method'] !== $method || $route['path'] !== $path) {
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                $middleware();
            }

            [$class, $action] = $route['action'];
            (new $class())->$action();
            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}