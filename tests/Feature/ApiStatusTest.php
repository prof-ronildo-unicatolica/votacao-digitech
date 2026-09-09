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

    public function test_terminal_sem_sessao(): void
    {
        $terminal = Terminal::factory()->create(['numero' => 7]);

        $this->getJson("/api/terminais/{$terminal->id}/status")
            ->assertOk()
            ->assertExactJson(['terminal' => 7, 'sessoes' => 0]);
    }

    public function test_terminal_com_sessao(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->getJson("/api/terminais/{$sessao->terminal_id}/status")
            ->assertOk()
            ->assertJson(['sessoes' => 1]);
    }

    public function test_terminal_inexistente_retorna_404(): void
    {
        $this->getJson('/api/terminais/999/status')->assertNotFound();
    }
}
