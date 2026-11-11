<?php

namespace Tests\Feature;

use App\Enums\SituacaoChapa;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\User;
use App\Models\Voto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/** H3 · A comissão homologa, indefere (com motivo), reabre, corrige e exclui chapas. */
class HomologacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Eleicao $eleicao;

    /** @var Collection<int, Cargo> */
    private Collection $cargos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Comissão Teste']);
        $this->actingAs($this->admin);

        $this->eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create();
        $this->cargos = collect(['Presidente', 'Tesoureiro'])->map(
            fn (string $titulo, int $i) => Cargo::factory()->for($this->eleicao)->create(['titulo' => $titulo, 'ordem' => $i + 1]),
        );
        foreach (range(1, 6) as $n) {
            Eleitor::factory()->create(['matricula' => "202410{$n}", 'nome' => "Aluno {$n}"]);
        }
    }

    /** Chapa com os alunos das matrículas dadas, na ordem dos cargos. */
    private function chapa(int $numero, array $matriculas, array $atributos = []): Chapa
    {
        $chapa = Chapa::factory()->for($this->eleicao)->create(['numero' => $numero, 'nome' => "Chapa {$numero}"] + $atributos);
        foreach ($this->cargos as $i => $cargo) {
            $chapa->candidatos()->create(['eleicao_id' => $this->eleicao->id, 'cargo_id' => $cargo->id, 'eleitor_id' => Eleitor::where('matricula', $matriculas[$i])->value('id')]);
        }

        return $chapa;
    }

    private function emHomologacao(): void
    {
        $this->travel(15)->days(); // inscrições fecham em 2 semanas a partir de ontem; votação abre 3 dias depois
    }

    public function test_lista_mostra_situacao_origem_e_composicao(): void
    {
        $this->chapa(1, ['2024101', '2024102']);
        $this->chapa(2, ['2024103', '2024104'], ['origem' => 'csv']);

        $this->get('/admin/chapas')->assertOk()
            ->assertSee('Inscritas')
            ->assertSeeInOrder(['Chapa 1 · Chapa 1', 'Formulário', 'Presidente', 'Aluno 1', '2024101', 'Tesoureiro', 'Aluno 2', '2024102'])
            ->assertSeeInOrder(['Chapa 2 · Chapa 2', 'CSV'])
            ->assertSee('Pendente')
            ->assertSee('Nenhuma chapa homologada');
    }

    public function test_filtro_por_situacao(): void
    {
        $this->chapa(1, ['2024101', '2024102'], ['situacao' => 'homologada']);
        $this->chapa(2, ['2024103', '2024104']);

        $this->get('/admin/chapas?situacao=pendente')->assertOk()->assertSee('Chapa 2 · Chapa 2')->assertDontSee('Chapa 1 · Chapa 1');
    }

    public function test_homologar(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102']);

        $this->post("/admin/chapas/{$chapa->id}/homologar")
            ->assertRedirect(route('admin.chapas.index', ['eleicao' => $this->eleicao->id]).'#chapa-1')
            ->assertSessionHas('status');

        $chapa->refresh();
        $this->assertSame(SituacaoChapa::Homologada, $chapa->situacao);
        $this->assertTrue($chapa->analisadaPor->is($this->admin));
        $this->assertNotNull($chapa->analisada_em);
        $this->get('/admin/chapas')->assertSee('Homologada em')->assertSee('por Comissão Teste');
    }

    public function test_indeferir_sem_motivo_e_rejeitado(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102']);

        $this->post("/admin/chapas/{$chapa->id}/indeferir", ['motivo' => ''])->assertSessionHasErrors('motivo');
        $this->post("/admin/chapas/{$chapa->id}/indeferir", ['motivo' => 'curto'])->assertSessionHasErrors('motivo');
        $this->get('/admin/chapas')->assertSee('Explique o motivo do indeferimento')->assertSee('curto</textarea>', false);
        $this->assertSame(SituacaoChapa::Pendente, $chapa->fresh()->situacao);

        $this->post("/admin/chapas/{$chapa->id}/indeferir", ['motivo' => 'O candidato a tesoureiro trancou a matrícula.'])->assertSessionHasNoErrors();
        $this->assertSame(SituacaoChapa::Indeferida, $chapa->fresh()->situacao);
        $this->get('/admin/chapas')->assertSee('Motivo do indeferimento')->assertSee('O candidato a tesoureiro trancou a matrícula.');
    }

    public function test_decisao_tomada_so_muda_depois_de_reabrir(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102'], ['situacao' => 'indeferida', 'motivo_indeferimento' => 'Motivo antigo do indeferimento.']);

        $this->post("/admin/chapas/{$chapa->id}/homologar")->assertSessionHasErrors('geral');
        $this->assertSame(SituacaoChapa::Indeferida, $chapa->fresh()->situacao);

        $this->post("/admin/chapas/{$chapa->id}/reabrir")->assertSessionHasNoErrors();
        $chapa->refresh();
        $this->assertSame(SituacaoChapa::Pendente, $chapa->situacao);
        $this->assertNull($chapa->motivo_indeferimento);

        $this->post("/admin/chapas/{$chapa->id}/homologar");
        $this->assertSame(SituacaoChapa::Homologada, $chapa->fresh()->situacao);
    }

    public function test_indeferida_volta_para_pendente_na_homologacao_mas_nao_depois_da_votacao_abrir(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102'], ['situacao' => 'indeferida', 'motivo_indeferimento' => 'Motivo do indeferimento.']);

        $this->emHomologacao();
        $this->post("/admin/chapas/{$chapa->id}/reabrir")->assertSessionHasNoErrors();
        $this->post("/admin/chapas/{$chapa->id}/homologar")->assertSessionHasNoErrors();

        $this->travel(5)->days(); // votação aberta
        $this->post("/admin/chapas/{$chapa->id}/reabrir")->assertSessionHasErrors('geral');
        $this->assertSame(SituacaoChapa::Homologada, $chapa->fresh()->situacao);
        $this->get('/admin/chapas')->assertSee('Somente leitura')->assertDontSee('Confirmar homologação');
    }

    public function test_so_homologadas_aparecem_na_urna(): void
    {
        $homologada = $this->chapa(1, ['2024101', '2024102'], ['situacao' => 'homologada']);
        $this->chapa(2, ['2024103', '2024104']);
        $this->chapa(3, ['2024105', '2024106'], ['situacao' => 'indeferida', 'motivo_indeferimento' => 'Motivo do indeferimento.']);

        $this->assertSame([$homologada->id], $this->eleicao->chapasNaUrna()->pluck('id')->all());
    }

    public function test_corrigir_candidato_com_as_regras_da_inscricao(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102'], ['situacao' => 'homologada']);
        $this->chapa(2, ['2024103', '2024104']);
        $tesoureiro = $this->cargos[1];
        $url = "/admin/chapas/{$chapa->id}/candidatos/{$tesoureiro->id}";

        $this->put($url, ['matricula' => '2024999'])->assertSessionHasErrors(['matricula' => 'Matrícula 2024999 não cadastrada no eleitorado.']);
        $this->put($url, ['matricula' => '2024103'])->assertSessionHasErrors(['matricula' => 'Aluno 3 já está em outra chapa desta eleição.']);
        $this->put($url, ['matricula' => '2024101'])->assertSessionHasErrors('matricula'); // já é o presidente desta chapa
        User::factory()->create(['perfil' => 'mesario', 'matricula' => '2024106']);
        $this->put($url, ['matricula' => '2024106'])->assertSessionHasErrors('matricula');

        $this->put($url, ['matricula' => '2024105'])->assertSessionHasNoErrors()->assertSessionHas('status');

        $chapa->refresh();
        $this->assertSame(['2024101', '2024105'], $chapa->composicao()->get()->pluck('eleitor.matricula')->all());
        $this->assertSame(SituacaoChapa::Pendente, $chapa->situacao, 'Composição mudou: a homologação anterior não vale mais.');
    }

    public function test_composicao_nao_muda_depois_do_fim_das_inscricoes(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102']);
        $this->emHomologacao();

        $this->put("/admin/chapas/{$chapa->id}/candidatos/{$this->cargos[1]->id}", ['matricula' => '2024105'])
            ->assertSessionHasErrors(['geral' => 'A composição não pode mais mudar: as inscrições foram encerradas em '.$this->eleicao->inscricao_fecha_em->format('d/m/Y \à\s H:i').'.']);

        $this->assertSame('2024102', $chapa->composicao()->get()->last()->eleitor->matricula);
        $this->get('/admin/chapas')->assertSee('a composição congelou')->assertDontSee('Nova matrícula para');
    }

    public function test_cargo_de_outra_eleicao_nao_e_aceito(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102']);
        $outro = Cargo::factory()->create();

        $this->put("/admin/chapas/{$chapa->id}/candidatos/{$outro->id}", ['matricula' => '2024105'])->assertNotFound();
    }

    public function test_excluir_chapa_sem_votos(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102']);

        $this->delete("/admin/chapas/{$chapa->id}")->assertSessionHasNoErrors();

        $this->assertModelMissing($chapa);
        $this->assertSame(0, $this->eleicao->candidatos()->count());
    }

    public function test_exclusao_bloqueada_com_votos(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102'], ['situacao' => 'homologada']);
        $this->eleicao->update(Eleicao::factory()->votacaoAberta()->raw());
        Voto::factory()->create(['eleicao_id' => $this->eleicao->id, 'chapa_id' => $chapa->id]);

        $this->delete("/admin/chapas/{$chapa->id}")->assertSessionHasErrors('geral');

        $this->assertModelExists($chapa);
    }

    public function test_avisos_de_nenhuma_chapa_homologada(): void
    {
        $this->chapa(1, ['2024101', '2024102']);

        $this->get('/admin')->assertSee('Nenhuma chapa homologada')->assertSee('0 homologadas · 1 pendentes');
        $this->get('/admin/eleicoes')->assertSee('Sem chapa homologada: a votação não pode começar');
    }

    public function test_mesario_nao_acessa(): void
    {
        $chapa = $this->chapa(1, ['2024101', '2024102']);
        $this->actingAs(User::factory()->create(['perfil' => 'mesario']));

        $this->get('/admin/chapas')->assertForbidden();
        $this->post("/admin/chapas/{$chapa->id}/homologar")->assertForbidden();
    }
}
