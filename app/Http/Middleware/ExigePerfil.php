<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Libera a rota só para os perfis informados; os demais recebem 403 (H1).
 * Uso nas rotas: ->middleware('perfil:admin') ou ->middleware('perfil:admin,mesario')
 */
class ExigePerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        abort_unless(in_array($request->user()?->perfil?->value, $perfis, true), 403, 'Seu perfil não tem acesso a esta página.');

        return $next($request);
    }
}
