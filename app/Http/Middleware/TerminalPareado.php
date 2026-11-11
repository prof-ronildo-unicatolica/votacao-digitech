<?php

namespace App\Http\Middleware;

use App\Models\Terminal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rotas da urna (D16): só o computador pareado entra. O terminal vem do cookie, nunca da URL
 * nem do formulário, e fica em $request->attributes->get('terminal').
 */
class TerminalPareado
{
    public function handle(Request $request, Closure $next): Response
    {
        $terminal = Terminal::porToken($request->cookie(Terminal::COOKIE));

        if (! $terminal) {
            $mensagem = 'Este computador não está ativado como terminal. Chame o mesário.';

            return $request->expectsJson()
                ? response()->json(['erro' => $mensagem], 403)
                : response()->view('urna.nao-ativado', ['mensagem' => $mensagem], 403);
        }

        $request->attributes->set('terminal', $terminal);

        return $next($request);
    }
}
