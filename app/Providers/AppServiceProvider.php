<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}
