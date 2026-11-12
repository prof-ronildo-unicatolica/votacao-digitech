<?php

namespace Tests\Feature;

use App\Models\Candidato;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** H4 · Admin cadastra eleitores um a um. A importação por CSV está em ImportacaoEleitoresTest. */
class EleitoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_cadastra_eleitor(): void
    {
        $this->post('/admin/eleitores', [
            'matricula' => ' 2024001 ', 'nome' => 'Ana   Lima', 'turma' => 'ADS 3', 'email' => '',
        ])->assertRedirect('/admin/eleitores');

        $this->assertDatabaseHas('eleitores', ['matricula' => '2024001', 'nome' => 'Ana Lima', 'turma' => 'ADS 3', 'email' => null]);
    }

    public function test_matricula_repetida_ou_invalida_e_nome_vazio_sao_rejeitados(): void
    {
        $existente = Eleitor::factory()->create();

        $this->post('/admin/eleitores', ['matricula' => $existente->matricula, 'nome' => 'Outro'])
            ->assertSessionHasErrors('matricula');
        $this->post('/admin/eleitores', ['matricula' => '2024 001', 'nome' => ''])
            ->assertSessionHasErrors(['matricula', 'nome']);
    }

    public function test_admin_edita_eleitor_mantendo_a_matricula(): void
    {
        $eleitor = Eleitor::factory()->create();

        $this->put("/admin/eleitores/{$eleitor->id}", ['matricula' => $eleitor->matricula, 'nome' => 'Nome Corrigido'])
            ->assertRedirect('/admin/eleitores');

        $this->assertSame('Nome Corrigido', $eleitor->refresh()->nome);
    }

    public function test_busca_por_matricula_ou_nome(): void
    {
        Eleitor::factory()->create(['matricula' => '2024555', 'nome' => 'Joana Prado']);
        Eleitor::factory()->create(['matricula' => '2023111', 'nome' => 'Marcos Teles']);

        $this->get('/admin/eleitores?busca=2024555')->assertOk()->assertSee('Joana Prado')->assertDontSee('Marcos Teles');
        $this->get('/admin/eleitores?busca=teles')->assertOk()->assertSee('Marcos Teles')->assertDontSee('Joana Prado');
    }

    public function test_eleitor_sem_vinculos_pode_ser_excluido(): void
    {
        $eleitor = Eleitor::factory()->create();

        $this->delete("/admin/eleitores/{$eleitor->id}")->assertRedirect('/admin/eleitores');

        $this->assertModelMissing($eleitor);
    }

    public function test_eleitor_liberado_ou_candidato_nao_pode_ser_excluido(): void
    {
        $liberado = SessaoVotacao::factory()->create()->eleitor;
        $candidato = Candidato::factory()->create()->eleitor;

        $this->delete("/admin/eleitores/{$liberado->id}")->assertSessionHasErrors(['geral' => "{$liberado->nome} já foi liberado para votar e faz parte da ata: não pode ser excluído."]);
        $this->delete("/admin/eleitores/{$candidato->id}")->assertSessionHasErrors('geral');

        $this->assertModelExists($liberado);
        $this->assertModelExists($candidato);
    }

    public function test_mesario_nao_acessa_eleitores(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/eleitores')->assertForbidden();
    }
}
