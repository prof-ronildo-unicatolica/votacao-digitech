<?php

namespace Tests\Feature;

use App\Enums\SituacaoSessao;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** H6 · O mesário confere a matrícula, escolhe o terminal e libera o voto (D21). */
class LiberacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $mesario;

    private Eleicao $eleicao;

    /** @var array<int, Terminal> */
    private array $terminal = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mesario = User::factory()->create(['perfil' => 'mesario', 'name' => 'Carlos Mesário']);
        $this->actingAs($this->mesario);

        $this->eleicao = Eleicao::factory()->votacaoAberta()->ativa()->create();
        Chapa::factory()->for($this->eleicao)->homologada()->create(['numero' => 1]);

        foreach ([1, 2, 3] as $n) {
            $this->terminal[$n] = Terminal::factory()->create(['numero' => $n]);
        }
        $this->terminal[1]->ativar();
        $this->terminal[2]->ativar(); // o 3 fica inativo

        Eleitor::factory()->create(['matricula' => '2024101', 'nome' => 'Maria Oliveira', 'turma' => 'SI 4']);
        Eleitor::factory()->create(['matricula' => '2024102', 'nome' => 'João Silva']);
    }

    private function liberar(string $matricula, int $terminal): TestResponse
    {
        return $this->post('/mesario/liberar', ['matricula' => $matricula, 'terminal_id' => $this->terminal[$terminal]->id]);
    }

    private function sessao(string $matricula = '2024101'): ?SessaoVotacao
    {
        return SessaoVotacao::whereHas('eleitor', fn ($q) => $q->where('matricula', $matricula))->first();
    }

    public function test_conferir_matricula_mostra_nome_e_turma_antes_de_liberar(): void
    {
        $this->get('/mesario?matricula=2024101')->assertOk()
            ->assertSee('Maria Oliveira')->assertSee('SI 4')
            ->assertSee('Liberar Maria Oliveira')
            ->assertSeeInOrder(['Terminal 1', 'Livre', 'Terminal 2', 'Livre', 'Terminal 3', 'Inativo']);

        $this->get('/mesario?matricula=2024999')->assertOk()->assertSee('Matrícula 2024999 não encontrada no eleitorado');
        $this->assertSame(0, SessaoVotacao::count(), 'Conferir não libera.');
    }

    public function test_liberacao_valida(): void
    {
        $this->liberar('2024101', 1)->assertRedirect('/mesario')->assertSessionHas('status');

        $sessao = $this->sessao();
        $this->assertSame(SituacaoSessao::Aberta, $sessao->situacao);
        $this->assertTrue($sessao->terminal->is($this->terminal[1]));
        $this->assertTrue($sessao->mesario->is($this->mesario));
        $this->assertEqualsWithDelta(now()->addMinutes(30)->timestamp, $sessao->expira_em->timestamp, 2);

        $this->assertSame('liberado', $this->terminal[1]->fresh()->estado());
        $this->get('/mesario')->assertSee('Maria Oliveira · expira em 30 min')->assertSee('Cancelar sessão');
    }

    public function test_eleitor_que_ja_votou_nao_e_liberado(): void
    {
        $this->liberar('2024101', 1);
        $this->sessao()->update(['situacao' => 'votou', 'votou_em' => now()]);

        $this->liberar('2024101', 2)->assertSessionHasErrors(['geral' => 'Maria Oliveira já votou nesta eleição, às '.now()->format('H:i').'.']);

        $this->assertSame(SituacaoSessao::Votou, $this->sessao()->situacao, 'Sessão que votou nunca reabre.');
        $this->travel(1)->hours();
        $this->liberar('2024101', 2)->assertSessionHasErrors('geral');
    }

    public function test_terminal_ocupado(): void
    {
        $this->liberar('2024101', 1);

        $this->liberar('2024102', 1)->assertSessionHasErrors(['geral' => 'O terminal 1 está com a liberação de Maria Oliveira (expira em 30 min). Escolha outro terminal ou cancele aquela sessão.']);
        $this->assertNull($this->sessao('2024102'));
    }

    public function test_terminal_inativo(): void
    {
        $this->liberar('2024101', 3)->assertSessionHasErrors('geral');
        $this->assertStringContainsString('não está ativado', session('errors')->first('geral'));

        // Pareamento vencido (24 h) também conta como inativo.
        $this->travel(25)->hours();
        $this->eleicao->update(['votacao_fecha_em' => now()->addHours(5)]);
        $this->liberar('2024101', 1)->assertSessionHasErrors('geral');
        $this->assertSame(0, SessaoVotacao::count());
    }

    public function test_fora_do_periodo_de_votacao(): void
    {
        $this->eleicao->update(Eleicao::factory()->inscricoesAbertas()->raw());
        $this->liberar('2024101', 1)->assertSessionHasErrors('geral');
        $this->assertStringContainsString('A votação ainda não abriu', session('errors')->first('geral'));

        $this->eleicao->update(['votacao_abre_em' => now()->subDays(2), 'votacao_fecha_em' => now()->subDay(), 'inscricao_abre_em' => now()->subDays(10), 'inscricao_fecha_em' => now()->subDays(5)]);
        $this->liberar('2024101', 1)->assertSessionHasErrors('geral');
        $this->assertStringContainsString('A votação foi encerrada', session('errors')->first('geral'));

        $this->get('/mesario')->assertSee('Liberação indisponível');
        $this->assertSame(0, SessaoVotacao::count());
    }

    public function test_sem_chapa_homologada_nao_libera(): void
    {
        $this->eleicao->chapas()->update(['situacao' => 'pendente']);

        $this->liberar('2024101', 1)->assertSessionHasErrors(['geral' => 'Nenhuma chapa foi homologada: a urna não teria em quem votar. Fale com a comissão eleitoral.']);
    }

    public function test_reliberacao_apos_expirar_reaproveita_a_mesma_sessao(): void
    {
        $this->liberar('2024101', 1);
        $id = $this->sessao()->id;

        $this->travel(31)->minutes();
        $this->assertSame(SituacaoSessao::Expirada, $this->sessao()->situacaoAtual());
        $this->assertSame('livre', $this->terminal[1]->fresh()->estado(), 'Sessão expirada libera o terminal.');

        $outro = User::factory()->create(['perfil' => 'mesario']);
        $this->actingAs($outro)->liberar('2024101', 2)->assertSessionHasNoErrors();

        $this->assertSame(1, SessaoVotacao::count(), 'Continua uma linha só em sessoes_votacao.');
        $sessao = $this->sessao();
        $this->assertSame($id, $sessao->id);
        $this->assertTrue($sessao->terminal->is($this->terminal[2]));
        $this->assertTrue($sessao->mesario->is($outro));
        $this->assertSame(SituacaoSessao::Aberta, $sessao->situacaoAtual());
    }

    public function test_cancelar_e_liberar_de_novo(): void
    {
        $this->liberar('2024101', 1);
        $sessao = $this->sessao();

        $this->post("/mesario/sessoes/{$sessao->id}/cancelar")->assertRedirect('/mesario')->assertSessionHas('status');
        $this->assertSame(SituacaoSessao::Cancelada, $sessao->fresh()->situacao);
        $this->assertSame('livre', $this->terminal[1]->fresh()->estado());

        $this->liberar('2024101', 2)->assertSessionHasNoErrors();
        $this->assertSame(1, SessaoVotacao::count());
        $this->assertNull($this->sessao()->cancelada_em);
    }

    public function test_nao_cancela_quem_ja_votou(): void
    {
        $this->liberar('2024101', 1);
        $sessao = $this->sessao();
        $sessao->update(['situacao' => 'votou', 'votou_em' => now()]);

        $this->post("/mesario/sessoes/{$sessao->id}/cancelar")->assertSessionHasErrors(['geral' => 'O eleitor já votou: a sessão não pode mais ser cancelada.']);
        $this->assertSame(SituacaoSessao::Votou, $sessao->fresh()->situacao);
    }

    public function test_eleitor_ja_liberado_em_outro_terminal(): void
    {
        $this->liberar('2024101', 1);

        $this->liberar('2024101', 2)->assertSessionHasErrors('geral');
        $this->assertStringContainsString('tem uma liberação em andamento no terminal 1', session('errors')->first('geral'));
        $this->assertTrue($this->sessao()->terminal->is($this->terminal[1]));
    }

    public function test_mesario_nao_libera_o_proprio_voto(): void
    {
        $this->mesario->update(['matricula' => '2024102']);

        $this->liberar('2024102', 1)->assertSessionHasErrors(['geral' => 'Você não pode liberar o seu próprio voto. Peça a outro mesário.']);
        $this->get('/mesario?matricula=2024102')->assertSee('Você não pode liberar o seu próprio voto')->assertDontSee('Liberar João Silva');
    }

    public function test_admin_ve_o_terminal_liberado_e_visitante_nao_entra(): void
    {
        $this->liberar('2024101', 1);

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/mesario')->assertOk();
        $this->get('/admin/terminais')->assertOk()->assertSee('Liberado');

        auth()->logout();
        $this->get('/mesario')->assertRedirect('/login');
        $this->post('/mesario/liberar', ['matricula' => '2024102', 'terminal_id' => $this->terminal[2]->id])->assertRedirect('/login');
    }
}
