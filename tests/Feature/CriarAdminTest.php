<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Comando que cria o primeiro admin em produção (H1, H12): php artisan digitech:admin */
class CriarAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_um_admin(): void
    {
        $this->artisan('digitech:admin', ['--email' => 'comissao@unicatolica.edu.br', '--nome' => 'Comissão'])
            ->expectsQuestion('Senha (mínimo 8 caracteres)', 'senha-forte')
            ->expectsQuestion('Confirme a senha', 'senha-forte')
            ->assertSuccessful();

        $admin = User::where('email', 'comissao@unicatolica.edu.br')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('senha-forte', $admin->password));
    }

    public function test_promove_e_reativa_usuario_existente(): void
    {
        $mesario = User::factory()->desativado()->create();

        $this->artisan('digitech:admin', ['--email' => $mesario->email])
            ->expectsQuestion('Senha (mínimo 8 caracteres)', 'nova-senha-123')
            ->expectsQuestion('Confirme a senha', 'nova-senha-123')
            ->assertSuccessful();

        $mesario->refresh();
        $this->assertTrue($mesario->isAdmin());
        $this->assertTrue($mesario->ativo);
    }

    public function test_recusa_senha_curta_ou_diferente(): void
    {
        $this->artisan('digitech:admin', ['--email' => 'x@y.com', '--nome' => 'X'])
            ->expectsQuestion('Senha (mínimo 8 caracteres)', 'curta')
            ->expectsQuestion('Confirme a senha', 'outra')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'x@y.com']);
    }
}
