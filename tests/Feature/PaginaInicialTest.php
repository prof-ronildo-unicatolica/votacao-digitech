<?php

namespace Tests\Feature;

use App\Models\Chapa;
use App\Models\Eleicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TESTE DE INTEGRAÇÃO (Laravel chama de "Feature test"): sobe a aplicação,
 * usa um banco real (SQLite em memória, ver phpunit.xml) e faz requisições HTTP.
 */
class PaginaInicialTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_inicial_abre_sem_eleicao_cadastrada(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Nenhuma eleição');
    }

    public function test_pagina_inicial_lista_as_chapas_de_cada_eleicao(): void
    {
        $eleicao = Eleicao::factory()->create();
        Chapa::factory()->for($eleicao)->create(['numero' => 10]);
        Chapa::factory()->for($eleicao)->create(['numero' => 20]);

        $this->get('/')
            ->assertOk()
            ->assertSee("Eleição #{$eleicao->id}")
            ->assertSeeInOrder(['Chapa <strong>10</strong>', 'Chapa <strong>20</strong>'], false);
    }

    public function test_telas_placeholder_respondem(): void
    {
        $this->get('/mesario')->assertOk();
        $this->get('/urna/1')->assertOk();
        $this->get('/admin')->assertOk();
    }
}
