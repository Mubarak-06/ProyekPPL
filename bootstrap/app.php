<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) =>
            route(match (true) {
                (bool) $request->user()?->is_admin => 'admin.dashboard',
                (bool) $request->user()?->isKurir() => 'kurir.dashboard',
                default => 'pelanggan.profil',
            })
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
