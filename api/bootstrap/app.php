<?php

use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Registra as rotas JSON com o prefixo /api.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Permite que a API reconheça a sessão do frontend e valide o CSRF.
        $middleware->statefulApi();
        // A API responde 401 em JSON sem buscar uma página de login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : route('login'));
        $middleware->alias(['admin' => EnsureAdmin::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Define quando os erros devem ser apresentados em JSON.
        $exceptions->shouldRenderJsonWhen(
            function (Request $request, Throwable $exception): bool {
                // Responde em JSON nas rotas da API ou quando o cliente solicitar.
                return $request->is('api/*') || $request->expectsJson();
            }
        );
    })->create();
