<?php

namespace Tests\Feature;

use App\Models\SessaoVotacao;
use App\Models\Terminal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_responde_json_com_banco_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['banco' => 'ok']);
    }

    public function test_terminal_sem_sessao_nao_esta_liberado(): void
    {
        $terminal = Terminal::factory()->create(['numero' => 7]);

        $this->getJson("/api/terminais/{$terminal->id}/status")
            ->assertOk()
            ->assertExactJson([
                'terminal' => 7,
                'liberada' => false,
                'eleitor' => null,
                'expira_em' => null,
            ]);
    }

    public function test_terminal_com_sessao_aberta_informa_o_eleitor(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->getJson("/api/terminais/{$sessao->terminal_id}/status")
            ->assertOk()
            ->assertJson(['liberada' => true, 'eleitor' => $sessao->eleitor->nome]);
    }

    public function test_sessao_expirada_nao_libera_o_terminal(): void
    {
        $sessao = SessaoVotacao::factory()->create(['expira_em' => now()->subMinute()]);

        $this->getJson("/api/terminais/{$sessao->terminal_id}/status")
            ->assertJson(['liberada' => false]);
    }

    public function test_terminal_inexistente_retorna_404(): void
    {
        $this->getJson('/api/terminais/999/status')->assertNotFound();
    }
}
