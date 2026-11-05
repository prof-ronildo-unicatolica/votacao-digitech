<?php

namespace Tests\Feature;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** H1 · Autenticar admin e mesário. */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(string $email, string $senha = 'password')
    {
        return $this->post('/login', ['email' => $email, 'password' => $senha]);
    }

    public function test_admin_entra_e_vai_para_o_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->entrar($admin->email)->assertRedirect(route('admin.painel'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_mesario_entra_e_vai_para_o_painel_do_mesario(): void
    {
        $mesario = User::factory()->create();

        $this->entrar($mesario->email)->assertRedirect(route('mesario'));
        $this->assertAuthenticatedAs($mesario);
    }

    public function test_senha_errada_nao_entra(): void
    {
        $usuario = User::factory()->create();

        $this->from('/login')->entrar($usuario->email, 'errada')
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
        $this->assertGuest();
    }

    public function test_usuario_desativado_nao_entra(): void
    {
        $usuario = User::factory()->desativado()->create();

        $this->entrar($usuario->email)->assertSessionHasErrors(['email' => 'Seu usuário foi desativado. Procure a comissão eleitoral.']);
        $this->assertGuest();
    }

    public function test_usuario_desativado_com_senha_errada_recebe_mensagem_generica(): void
    {
        $usuario = User::factory()->desativado()->create();

        $this->entrar($usuario->email, 'errada')->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
    }

    public function test_cinco_tentativas_erradas_bloqueiam_por_um_minuto(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < LoginRequest::MAX_TENTATIVAS; $i++) {
            $this->entrar($usuario->email, 'errada');
        }

        // Nem a senha certa entra enquanto está bloqueado.
        $this->entrar($usuario->email)->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('email'));

        $this->travel(61)->seconds();
        $this->entrar($usuario->email)->assertRedirect(route('mesario'));
    }

    public function test_acertar_a_senha_zera_as_tentativas(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < LoginRequest::MAX_TENTATIVAS - 1; $i++) {
            $this->entrar($usuario->email, 'errada');
        }
        $this->entrar($usuario->email);

        $this->assertSame(0, RateLimiter::attempts(strtolower($usuario->email).'|127.0.0.1'));
    }

    public function test_mesario_recebe_403_no_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/eleicoes')->assertForbidden();
    }

    public function test_admin_tambem_acessa_o_painel_do_mesario(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/mesario')->assertOk();
    }

    public function test_visitante_e_mandado_para_o_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/mesario')->assertRedirect('/login');
    }

    public function test_quem_ja_esta_logado_e_abre_o_login_vai_para_sua_pagina(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/login')->assertRedirect(route('admin.painel'));
    }

    public function test_logout_encerra_a_sessao(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_usuario_desativado_durante_a_sessao_e_derrubado(): void
    {
        $mesario = User::factory()->create();
        $this->actingAs($mesario)->get('/mesario')->assertOk();

        $mesario->update(['ativo' => false]);

        $this->get('/mesario')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_tela_de_login_tem_csrf_e_aponta_para_o_post(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('ainda não foi implementada');
    }
}
