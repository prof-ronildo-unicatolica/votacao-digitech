<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra serviços da aplicação no container.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa serviços da aplicação (executa após todos os providers registrarem).
     */
    public function boot(): void
    {
        // Links de paginação no visual do Bootstrap 5 (o padrão do Laravel é Tailwind).
        Paginator::useBootstrapFive();

        // H14 · Formulário público de inscrição: 5 envios por minuto por IP, com mensagem na tela.
        RateLimiter::for('inscricao', fn (Request $request) => Limit::perMinute(5)->by($request->ip())
            ->response(fn () => back()->withInput()->withErrors([
                'geral' => 'Muitos envios seguidos deste computador. Aguarde 1 minuto e envie de novo.',
            ])));

        // H14 · Conferência do nome pela matrícula: limita quem tentar varrer matrículas.
        RateLimiter::for('inscricao-consulta', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
