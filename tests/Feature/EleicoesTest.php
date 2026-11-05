<?php

namespace Tests\Feature;

use App\Enums\FaseEleicao;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\SessaoVotacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** H2 · Cadastrar eleição com período de inscrição e de votação. */
class EleicoesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function dados(array $troca = []): array
    {
        return $troca + [
            'titulo' => 'Centro Acadêmico 2026.2',
            'inscricao_abre_em' => '2026-11-10T08:00',
            'inscricao_fecha_em' => '2026-11-20T18:00',
            'votacao_abre_em' => '2026-11-25T08:00',
            'votacao_fecha_em' => '2026-11-25T18:00',
        ];
    }

    public function test_cria_eleicao_valida(): void
    {
        $this->post('/admin/eleicoes', $this->dados())->assertRedirect('/admin/eleicoes')->assertSessionHasNoErrors();

        $eleicao = Eleicao::sole();
        $this->assertSame('Centro Acadêmico 2026.2', $eleicao->titulo);
        $this->assertSame('2026-11-20 18:00', $eleicao->inscricao_fecha_em->format('Y-m-d H:i'));
    }

    public function test_primeira_eleicao_ja_nasce_ativa_e_as_seguintes_nao(): void
    {
        $this->post('/admin/eleicoes', $this->dados());
        $this->post('/admin/eleicoes', $this->dados(['titulo' => 'Segunda']));

        $this->assertSame('Centro Acadêmico 2026.2', Eleicao::atual()->titulo);
        $this->assertFalse(Eleicao::where('titulo', 'Segunda')->sole()->ativa);
    }

    public function test_campos_obrigatorios(): void
    {
        $this->post('/admin/eleicoes', [])
            ->assertSessionHasErrors(['titulo', 'inscricao_abre_em', 'inscricao_fecha_em', 'votacao_abre_em', 'votacao_fecha_em']);
    }

    public function test_fim_das_inscricoes_antes_da_abertura_e_rejeitado(): void
    {
        $this->post('/admin/eleicoes', $this->dados(['inscricao_fecha_em' => '2026-11-10T07:00']))
            ->assertSessionHasErrors(['inscricao_fecha_em' => 'O fim das inscrições precisa ser depois da abertura das inscrições.']);
    }

    public function test_votacao_antes_do_fim_das_inscricoes_e_rejeitada(): void
    {
        $this->post('/admin/eleicoes', $this->dados(['votacao_abre_em' => '2026-11-19T08:00']))
            ->assertSessionHasErrors(['votacao_abre_em' => 'O início da votação não pode ser antes do fim das inscrições.']);
    }

    public function test_votacao_pode_comecar_exatamente_no_fim_das_inscricoes(): void
    {
        $this->post('/admin/eleicoes', $this->dados(['votacao_abre_em' => '2026-11-20T18:00']))->assertSessionHasNoErrors();
    }

    public function test_fim_da_votacao_antes_do_inicio_e_rejeitado(): void
    {
        $this->post('/admin/eleicoes', $this->dados(['votacao_fecha_em' => '2026-11-25T08:00']))
            ->assertSessionHasErrors(['votacao_fecha_em' => 'O fim da votação precisa ser depois do início da votação.']);
    }

    public function test_ativar_uma_eleicao_desativa_a_anterior(): void
    {
        $antiga = Eleicao::factory()->ativa()->create();
        $nova = Eleicao::factory()->create();

        $this->post("/admin/eleicoes/{$nova->id}/ativar")->assertSessionHas('status');

        $this->assertTrue($nova->refresh()->ativa);
        $this->assertFalse($antiga->refresh()->ativa);
        $this->assertSame(1, Eleicao::where('ativa', true)->count());
    }

    public function test_desativar_deixa_sem_eleicao_ativa(): void
    {
        $eleicao = Eleicao::factory()->ativa()->create();

        $this->post("/admin/eleicoes/{$eleicao->id}/desativar");

        $this->assertNull(Eleicao::atual());
    }

    public function test_com_chapa_inscrita_o_fim_das_inscricoes_so_pode_ser_prorrogado(): void
    {
        $eleicao = Eleicao::factory()->create($this->datas());
        Chapa::factory()->for($eleicao)->create();

        $this->put("/admin/eleicoes/{$eleicao->id}", $this->dados(['inscricao_fecha_em' => '2026-11-19T18:00']))
            ->assertSessionHasErrors(['inscricao_fecha_em' => 'Já há chapas inscritas: o fim das inscrições só pode ser prorrogado, nunca antecipado.']);

        $this->put("/admin/eleicoes/{$eleicao->id}", $this->dados(['inscricao_fecha_em' => '2026-11-22T18:00']))
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-11-22 18:00', $eleicao->refresh()->inscricao_fecha_em->format('Y-m-d H:i'));
    }

    public function test_com_chapa_inscrita_a_abertura_das_inscricoes_nao_muda(): void
    {
        $eleicao = Eleicao::factory()->create($this->datas());
        Chapa::factory()->for($eleicao)->create();

        $this->put("/admin/eleicoes/{$eleicao->id}", $this->dados(['inscricao_abre_em' => '2026-11-09T08:00']))
            ->assertSessionHasErrors('inscricao_abre_em');
    }

    public function test_sem_chapas_qualquer_data_pode_mudar(): void
    {
        $eleicao = Eleicao::factory()->create($this->datas());

        $this->put("/admin/eleicoes/{$eleicao->id}", $this->dados(['inscricao_abre_em' => '2026-11-01T08:00', 'inscricao_fecha_em' => '2026-11-05T08:00']))
            ->assertSessionHasNoErrors();
    }

    public function test_exclui_eleicao_cadastrada_errada(): void
    {
        $eleicao = Eleicao::factory()->create();

        $this->delete("/admin/eleicoes/{$eleicao->id}")->assertRedirect('/admin/eleicoes');
        $this->assertModelMissing($eleicao);
    }

    public function test_nao_exclui_eleicao_com_chapas_ou_eleitores_liberados(): void
    {
        $comChapa = Chapa::factory()->create()->eleicao;
        $comSessao = SessaoVotacao::factory()->create()->eleicao;

        $this->delete("/admin/eleicoes/{$comChapa->id}")->assertSessionHasErrors('geral');
        $this->delete("/admin/eleicoes/{$comSessao->id}")->assertSessionHasErrors('geral');

        $this->assertModelExists($comChapa);
        $this->assertModelExists($comSessao);
    }

    public function test_lista_e_painel_mostram_a_fase_atual(): void
    {
        $eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create(['titulo' => 'Eleição em andamento']);

        $this->assertSame(FaseEleicao::InscricoesAbertas, $eleicao->fase());
        $this->get('/admin/eleicoes')->assertOk()->assertSee('Eleição em andamento')->assertSee('Inscrições abertas');
        $this->get('/admin')->assertOk()->assertSee('Eleição em andamento')->assertSee('Inscrições abertas');
    }

    public function test_formularios_abrem(): void
    {
        $eleicao = Eleicao::factory()->create();

        $this->get('/admin/eleicoes/create')->assertOk()->assertSee('name="inscricao_abre_em"', false);
        $this->get("/admin/eleicoes/{$eleicao->id}/edit")->assertOk()->assertSee($eleicao->titulo);
    }

    public function test_painel_sem_eleicao_ativa_convida_a_cadastrar(): void
    {
        $this->get('/admin')->assertOk()->assertSee('Nenhuma eleição ativa');
    }

    /** As mesmas datas de dados(), no formato do banco. */
    private function datas(): array
    {
        return [
            'inscricao_abre_em' => '2026-11-10 08:00',
            'inscricao_fecha_em' => '2026-11-20 18:00',
            'votacao_abre_em' => '2026-11-25 08:00',
            'votacao_fecha_em' => '2026-11-25 18:00',
        ];
    }
}
