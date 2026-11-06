<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usuário desativado pelo admin perde o acesso na próxima requisição, mesmo que já
 * estivesse logado (H1). Roda depois de `auth`.
 */
class UsuarioAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->ativo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Seu usuário foi desativado. Procure a comissão eleitoral.']);
        }

        return $next($request);
    }
}
