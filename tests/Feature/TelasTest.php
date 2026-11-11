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

    public function test_urna_do_terminal_pareado_aguarda_liberacao(): void
    {
        $token = Terminal::factory()->create(['numero' => 7])->ativar();

        $this->withCookie(Terminal::COOKIE, $token)->get('/urna')
            ->assertOk()
            ->assertSee('Terminal 7')
            ->assertSee('Aguardando liberação');
    }

    public function test_urna_nao_tem_mais_o_id_do_terminal_na_url(): void
    {
        $terminal = Terminal::factory()->create();

        $this->get("/urna/{$terminal->id}")->assertNotFound();
    }
}
