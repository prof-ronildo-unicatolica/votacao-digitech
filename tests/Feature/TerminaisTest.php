<?php

namespace Tests\Feature;

use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** H5 · Admin cadastra terminais; o computador da urna é pareado por cookie (D16). */
class TerminaisTest extends TestCase
{
    use RefreshDatabase;

    /** Ativa o terminal como um mesário faria e devolve o token do cookie. */
    private function ativarComoMesario(Terminal $terminal): string
    {
        $resposta = $this->actingAs(User::factory()->create())
            ->post('/terminal/ativar', ['terminal_id' => $terminal->id]);

        $resposta->assertRedirect('/urna');

        return $resposta->getCookie(Terminal::COOKIE)->getValue();
    }

    public function test_admin_cadastra_terminal_com_numero_unico(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post('/admin/terminais', ['numero' => 3, 'local' => 'Laboratório 2'])->assertRedirect('/admin/terminais');
        $this->assertDatabaseHas('terminais', ['numero' => 3, 'local' => 'Laboratório 2', 'token_hash' => null]);

        $this->post('/admin/terminais', ['numero' => 3])->assertSessionHasErrors('numero');
    }

    public function test_ativacao_grava_so_o_hash_e_desloga_o_usuario(): void
    {
        $terminal = Terminal::factory()->create(['numero' => 4]);

        $resposta = $this->actingAs(User::factory()->create())
            ->post('/terminal/ativar', ['terminal_id' => $terminal->id]);

        $resposta->assertRedirect('/urna');
        $this->assertGuest();

        $cookie = $resposta->getCookie(Terminal::COOKIE);
        $token = $cookie->getValue();
        $this->assertSame(64, strlen($token));
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->getSameSite());
        $this->assertEqualsWithDelta(now()->addDay()->timestamp, $cookie->getExpiresTime(), 5);

        $terminal->refresh();
        $this->assertSame(hash('sha256', $token), $terminal->token_hash);
        $this->assertNotNull($terminal->ativado_em);
        $this->assertSame(0, DB::table('terminais')->where('token_hash', $token)->count(), 'O token em texto puro não pode ir para o banco.');
        $this->assertArrayNotHasKey('token_hash', $terminal->toArray());
    }

    public function test_computador_pareado_abre_a_urna_e_o_status(): void
    {
        $token = $this->ativarComoMesario(Terminal::factory()->create(['numero' => 7]));

        $this->withCookie(Terminal::COOKIE, $token)->get('/urna')
            ->assertOk()->assertSee('Terminal 7')->assertSee('Aguardando liberação');

        $this->withCredentials()->withCookie(Terminal::COOKIE, $token)->getJson('/urna/status')
            ->assertOk()->assertExactJson(['terminal' => 7, 'liberada' => false, 'versao' => null]);
    }

    public function test_sem_cookie_ou_com_cookie_invalido_a_urna_responde_403(): void
    {
        Terminal::factory()->create();

        $this->get('/urna')->assertForbidden()->assertSee('Este computador não está ativado como terminal. Chame o mesário.');
        $this->getJson('/urna/status')->assertForbidden();
        $this->withCookie(Terminal::COOKIE, str_repeat('a', 64))->get('/urna')->assertForbidden();
    }

    public function test_reativar_em_outro_computador_invalida_o_cookie_antigo(): void
    {
        $terminal = Terminal::factory()->create();

        $primeiro = $this->ativarComoMesario($terminal);
        $segundo = $this->ativarComoMesario($terminal);

        $this->assertNotSame($primeiro, $segundo);
        $this->withCookie(Terminal::COOKIE, $primeiro)->get('/urna')->assertForbidden();
        $this->withCookie(Terminal::COOKIE, $segundo)->get('/urna')->assertOk();
    }

    public function test_pareamento_expira_em_24_horas_mesmo_com_o_cookie_copiado(): void
    {
        $token = $this->ativarComoMesario(Terminal::factory()->create());

        $this->travel(23)->hours();
        $this->withCookie(Terminal::COOKIE, $token)->get('/urna')->assertOk();

        $this->travel(2)->hours();
        $this->withCookie(Terminal::COOKIE, $token)->get('/urna')->assertForbidden();
    }

    public function test_admin_desativa_o_terminal_e_o_computador_perde_o_acesso(): void
    {
        $terminal = Terminal::factory()->create();
        $token = $this->ativarComoMesario($terminal);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/terminais/{$terminal->id}/desativar")->assertRedirect();

        $this->assertNull($terminal->refresh()->token_hash);
        $this->withCookie(Terminal::COOKIE, $token)->get('/urna')->assertForbidden();
    }

    public function test_visitante_sem_login_nao_acessa_a_ativacao(): void
    {
        $terminal = Terminal::factory()->create();

        $this->get('/terminal/ativar')->assertRedirect('/login');
        $this->post('/terminal/ativar', ['terminal_id' => $terminal->id])->assertRedirect('/login');

        $this->assertNull($terminal->refresh()->token_hash);
    }

    public function test_lista_mostra_inativo_e_livre(): void
    {
        $this->ativarComoMesario(Terminal::factory()->create(['numero' => 1]));
        Terminal::factory()->create(['numero' => 2]);

        $this->actingAs(User::factory()->admin()->create())->get('/admin/terminais')
            ->assertOk()->assertSeeInOrder(['Terminal 1', 'Livre', 'Terminal 2', 'Inativo']);
    }

    public function test_terminal_com_sessoes_nao_pode_ser_excluido(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $comSessao = SessaoVotacao::factory()->create()->terminal;
        $semSessao = Terminal::factory()->create();

        $this->delete("/admin/terminais/{$comSessao->id}")->assertSessionHasErrors('geral');
        $this->assertModelExists($comSessao);

        $this->delete("/admin/terminais/{$semSessao->id}")->assertRedirect('/admin/terminais');
        $this->assertModelMissing($semSessao);
    }

    public function test_mesario_ativa_mas_nao_administra_terminais(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/terminal/ativar')->assertOk();
        $this->get('/admin/terminais')->assertForbidden();
    }
}
