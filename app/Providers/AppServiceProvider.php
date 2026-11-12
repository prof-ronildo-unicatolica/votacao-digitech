<?php

namespace App\Providers;

use App\Listeners\AuditarAcesso;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra serviços da aplicação no container.
     */
    public function register(): void
    {
        // Teste de ponta a ponta (e2e/, D28): o Playwright adianta o relógio da aplicação para
        // passar de inscrições a homologação, votação e encerramento sem esperar horas. Ele grava
        // os segundos a adiantar neste arquivo. Só com APP_ENV=e2e: em qualquer outro ambiente,
        // o arquivo é ignorado.
        if ($this->app->environment('e2e') && is_file($relogio = storage_path('framework/e2e-relogio'))) {
            Carbon::setTestNow(Carbon::now()->addSeconds((int) file_get_contents($relogio)));
        }
    }

    /**
     * Inicializa serviços da aplicação (executa após todos os providers registrarem).
     */
    public function boot(): void
    {
        // Links de paginação no visual do Bootstrap 5 (o padrão do Laravel é Tailwind).
        Paginator::useBootstrapFive();

        // H11 · Auditoria de acesso (D22). Métodos com nome, então registrados aqui (a descoberta
        // automática do Laravel só enxerga handle()).
        Event::listen(Login::class, [AuditarAcesso::class, 'login']);
        Event::listen(Failed::class, [AuditarAcesso::class, 'falhou']);
        Event::listen(Lockout::class, [AuditarAcesso::class, 'bloqueado']);
        Event::listen(Logout::class, [AuditarAcesso::class, 'logout']);

        // H14 · Formulário público de inscrição: 5 envios por minuto por IP, com mensagem na tela.
        RateLimiter::for('inscricao', fn (Request $request) => Limit::perMinute(5)->by($request->ip())
            ->response(fn () => back()->withInput()->withErrors([
                'geral' => 'Muitos envios seguidos deste computador. Aguarde 1 minuto e envie de novo.',
            ])));

        // H14 · Conferência do nome pela matrícula: limita quem tentar varrer matrículas.
        RateLimiter::for('inscricao-consulta', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
