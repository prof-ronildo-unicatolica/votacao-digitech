<?php

use App\Http\Middleware\ExigePerfil;
use App\Http\Middleware\TerminalPareado;
use App\Http\Middleware\UsuarioAtivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'perfil' => ExigePerfil::class,
            'ativo' => UsuarioAtivo::class,
            'terminal' => TerminalPareado::class,
        ]);

        // Quem já está logado e abre /login vai para a página do seu perfil (H1).
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->paginaInicial() ?? '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
