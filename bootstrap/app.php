<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'central' => \App\Http\Middleware\EnsureCentralDomain::class,
    ]);

    $middleware->redirectGuestsTo(
        fn ($request) => $request->is('super*') ? route('super.login') : route('login')
    );

    $middleware->redirectUsersTo(
        fn ($request) => $request->is('super*') ? '/super' : '/dashboard'
    );
})

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
