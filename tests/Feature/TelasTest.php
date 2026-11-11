<?php

namespace Tests\Feature;

use App\Models\Terminal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Telas reais com a identidade visual: login (H1) e urna do terminal (H7). */
class TelasTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_mostra_formulario_com_csrf(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('css/digitech.css')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_urna_do_terminal_aguarda_liberacao(): void
    {
        $terminal = Terminal::factory()->create(['numero' => 7]);

        $this->get("/urna/{$terminal->id}")
            ->assertOk()
            ->assertSee('Terminal 7')
            ->assertSee('Aguardando liberação');
    }

    public function test_urna_de_terminal_inexistente_da_404(): void
    {
        $this->get('/urna/00000000-0000-0000-0000-000000000000')->assertNotFound();
    }
}
