<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Registra as rotas JSON com o prefixo /api, sem instalar Sanctum nesta etapa.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    // Define quando os erros devem ser apresentados em JSON.
    $exceptions->shouldRenderJsonWhen(
        function (Request $request, \Throwable $exception): bool {
            // Responde em JSON nas rotas da API ou quando o cliente solicitar.
            return $request->is('api/*') || $request->expectsJson();
        }
    );
})->create();
