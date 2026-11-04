<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFrontendSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // O Sanctum inicia a sessão e verifica o CSRF nas origens configuradas.
        abort_unless($request->hasSession(), 419, 'Inicialize a sessão em /sanctum/csrf-cookie e envie a origem do frontend.');

        return $next($request);
    }
}
