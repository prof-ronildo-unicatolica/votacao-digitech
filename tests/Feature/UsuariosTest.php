<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Models\SessaoVotacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** H1 · Admin gerencia admins e mesários. */
class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_admin_cadastra_mesario(): void
    {
        $this->post('/admin/usuarios', [
            'name' => 'Maria Mesária',
            'email' => 'maria@digitech.local',
            'perfil' => 'mesario',
            'password' => 'senha-forte',
            'password_confirmation' => 'senha-forte',
        ])->assertRedirect('/admin/usuarios');

        $maria = User::where('email', 'maria@digitech.local')->firstOrFail();
        $this->assertSame(Perfil::Mesario, $maria->perfil);
        $this->assertTrue($maria->ativo);
        $this->assertTrue(Hash::check('senha-forte', $maria->password));
    }

    public function test_email_repetido_e_senha_curta_sao_rejeitados(): void
    {
        $this->post('/admin/usuarios', [
            'name' => 'Outro',
            'email' => $this->admin->email,
            'perfil' => 'mesario',
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_editar_sem_senha_mantem_a_senha_atual(): void
    {
        $mesario = User::factory()->create();
        $hashAntes = $mesario->password;

        $this->put("/admin/usuarios/{$mesario->id}", [
            'name' => 'Nome Novo', 'email' => $mesario->email, 'perfil' => 'admin', 'password' => '', 'password_confirmation' => '',
        ])->assertRedirect('/admin/usuarios');

        $mesario->refresh();
        $this->assertSame('Nome Novo', $mesario->name);
        $this->assertSame(Perfil::Admin, $mesario->perfil);
        $this->assertSame($hashAntes, $mesario->password);
    }

    public function test_admin_desativa_e_reativa_um_usuario(): void
    {
        $mesario = User::factory()->create();

        $this->post("/admin/usuarios/{$mesario->id}/ativo");
        $this->assertFalse($mesario->refresh()->ativo);

        $this->post("/admin/usuarios/{$mesario->id}/ativo");
        $this->assertTrue($mesario->refresh()->ativo);
    }

    public function test_admin_nao_desativa_exclui_nem_rebaixa_a_si_mesmo(): void
    {
        $this->post("/admin/usuarios/{$this->admin->id}/ativo")->assertSessionHasErrors('geral');
        $this->delete("/admin/usuarios/{$this->admin->id}")->assertSessionHasErrors('geral');
        $this->put("/admin/usuarios/{$this->admin->id}", [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'perfil' => 'mesario',
        ])->assertSessionHasErrors('perfil');

        $this->admin->refresh();
        $this->assertTrue($this->admin->ativo);
        $this->assertTrue($this->admin->isAdmin());
    }

    public function test_usuario_sem_liberacoes_pode_ser_excluido(): void
    {
        $mesario = User::factory()->create();

        $this->delete("/admin/usuarios/{$mesario->id}")->assertRedirect('/admin/usuarios');
        $this->assertModelMissing($mesario);
    }

    public function test_usuario_que_ja_liberou_eleitores_nao_pode_ser_excluido(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->delete("/admin/usuarios/{$sessao->mesario_id}")->assertSessionHasErrors('geral');
        $this->assertModelExists($sessao->mesario);
    }

    public function test_lista_mostra_os_usuarios_e_a_situacao(): void
    {
        User::factory()->desativado()->create(['name' => 'Fulano Desativado']);

        $this->get('/admin/usuarios')->assertOk()->assertSee('Fulano Desativado')->assertSee('Desativado')->assertSee('(você)');
    }
}
