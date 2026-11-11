<?php

namespace Tests\Feature;

use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Terminal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EXEMPLO de TESTE DE INTEGRAÇÃO (Laravel chama de "Feature test"): sobe a aplicação,
 * usa um banco real (SQLite em memória, ver phpunit.xml) e faz requisições HTTP.
 */
class PaginaInicialTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_inicial_lista_as_chapas_de_cada_eleicao(): void
    {
        $eleicao = Eleicao::factory()->create();
        Chapa::factory()->for($eleicao)->create(['numero' => 10]);
        Chapa::factory()->for($eleicao)->create(['numero' => 20]);

        $this->get('/')
            ->assertOk()
            ->assertSee($eleicao->id)
            ->assertSeeInOrder(['Chapa <strong>10</strong>', 'Chapa <strong>20</strong>'], false);
    }

    public function test_health_responde_json_com_banco_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['banco' => 'ok']);
    }

    public function test_status_do_terminal_e_acessado_pelo_uuid_e_nao_pelo_numero(): void
    {
        $terminal = Terminal::factory()->create(['numero' => 1]);

        $this->getJson("/api/terminais/{$terminal->id}/status")
            ->assertOk()
            ->assertJson(['terminal' => 1]);

        $this->getJson('/api/terminais/1/status')->assertNotFound();
    }

    public function test_pagina_inicial_aponta_o_status_do_terminal_pelo_uuid(): void
    {
        $terminal = Terminal::factory()->create(['numero' => 1]);

        $this->get('/')->assertOk()->assertSee("/api/terminais/{$terminal->id}/status", false);
    }

    public function test_telas_placeholder_usam_a_identidade_visual(): void
    {
        foreach (['/mesario', '/admin'] as $url) {
            $this->get($url)->assertOk()->assertSee('css/digitech.css')->assertSee('Tela ainda não implementada');
        }
    }
}
