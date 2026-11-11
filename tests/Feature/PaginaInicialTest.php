<?php

namespace Tests\Feature;

use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EXEMPLO de TESTE DE INTEGRAÇÃO (Laravel chama de "Feature test"): sobe a aplicação,
 * usa um banco real (SQLite em memória, ver phpunit.xml) e faz requisições HTTP.
 */
class PaginaInicialTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_inicial_lista_so_as_chapas_homologadas(): void
    {
        $eleicao = Eleicao::factory()->create();
        Chapa::factory()->for($eleicao)->homologada()->create(['numero' => 10]);
        Chapa::factory()->for($eleicao)->homologada()->create(['numero' => 20]);
        Chapa::factory()->for($eleicao)->create(['numero' => 30]);
        Chapa::factory()->for($eleicao)->indeferida()->create(['numero' => 40]);

        $this->get('/')
            ->assertOk()
            ->assertSee($eleicao->id)
            ->assertSeeInOrder(['Chapa <strong>10</strong>', 'Chapa <strong>20</strong>'], false)
            ->assertDontSee('Chapa <strong>30</strong>', false)
            ->assertDontSee('Chapa <strong>40</strong>', false);
    }

    public function test_pagina_inicial_mostra_a_composicao_de_cada_chapa(): void
    {
        $eleicao = Eleicao::factory()->create();
        $cargos = Cargo::factory()->count(5)->for($eleicao)->create();
        $chapa = Chapa::factory()->for($eleicao)->homologada()->create(['numero' => 10]);
        foreach ($cargos->take(3) as $cargo) {
            Candidato::factory()->for($chapa)->for($cargo)->create();
        }

        $this->get('/')->assertOk()->assertSee('5 cargos por chapa')->assertSee('3 de 5 candidatos');
    }

    public function test_health_responde_json_com_banco_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['banco' => 'ok']);
    }

    public function test_status_do_terminal_nao_e_mais_publico_na_api(): void
    {
        $terminal = Terminal::factory()->create(['numero' => 1]);

        $this->getJson("/api/terminais/{$terminal->id}/status")->assertNotFound();
        $this->getJson('/urna/status')->assertForbidden();
    }

    public function test_pagina_inicial_aponta_a_ativacao_do_terminal(): void
    {
        $this->get('/')->assertOk()->assertSee('/terminal/ativar', false);
    }

    public function test_painel_do_mesario_ainda_e_placeholder_com_a_identidade_visual(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/mesario')
            ->assertOk()
            ->assertSee('css/digitech.css')
            ->assertSee('Tela ainda não implementada');
    }
}
