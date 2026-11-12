<?php

namespace Tests\Feature;

use App\Enums\AcaoAuditoria;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\EventoAuditoria;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** H11 fase 3 · Tela /admin/auditoria: filtros, detalhes, paginação, CSV e só admin. */
class AuditoriaTelaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $mesario;

    private Eleicao $eleicao;

    private Chapa $chapa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
        $this->mesario = User::factory()->create(['perfil' => 'mesario', 'name' => 'Carlos Mesário']);
        $this->eleicao = Eleicao::factory()->ativa()->create(['titulo' => 'Colegiado 2026.2']);
        $this->chapa = Chapa::factory()->for($this->eleicao)->create(['numero' => 20]);

        $this->travelTo('2026-11-10 09:00:00');
        Auditoria::registrar(AcaoAuditoria::ChapaHomologada, $this->chapa, ['numero' => 20], usuario: $this->admin);
        $this->travelTo('2026-11-11 10:00:00');
        Auditoria::registrar(AcaoAuditoria::SessaoLiberada, null, ['matricula' => '2024101', 'terminal' => 1], $this->eleicao->id, $this->mesario);
        Auditoria::registrar(AcaoAuditoria::Login, usuario: $this->mesario);
        $this->travelBack();
    }

    public function test_so_o_admin_ve_a_auditoria(): void
    {
        $this->actingAs($this->mesario)->get('/admin/auditoria')->assertForbidden();
        $this->actingAs($this->mesario)->get('/admin/auditoria/exportar.csv')->assertForbidden();
        auth()->logout();
        $this->get('/admin/auditoria')->assertRedirect('/login');
    }

    public function test_abre_na_eleicao_ativa_com_os_mais_recentes_primeiro(): void
    {
        $this->actingAs($this->admin)->get('/admin/auditoria')->assertOk()
            ->assertSee('2 eventos')
            ->assertSeeInOrder(['Eleitor liberado', 'Carlos Mesário', 'matricula: 2024101 · terminal: 1', 'Chapa homologada', 'Ana Admin'])
            ->assertSee('10:00:00')
            ->assertDontSee('dt-mono">acesso.login<', false);

        $this->get('/admin/auditoria?eleicao=todas')->assertSee('3 eventos')->assertSee('dt-mono">acesso.login<', false);
    }

    public function test_filtra_por_categoria_acao_usuario_periodo_e_busca(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/auditoria?grupo=chapas')->assertSee('1 evento')->assertSee('Chapa homologada');
        $this->get('/admin/auditoria?acao=sessao.liberada')->assertSee('1 evento')->assertSee('Eleitor liberado');
        $this->get("/admin/auditoria?eleicao=todas&usuario={$this->mesario->id}")->assertSee('2 eventos');
        $this->get('/admin/auditoria?de=2026-11-11&ate=2026-11-11')->assertSee('1 evento')->assertSee('Eleitor liberado');
        $this->get('/admin/auditoria?ate=2026-11-10')->assertSee('1 evento')->assertSee('Chapa homologada');
        $this->get('/admin/auditoria?busca=2024101')->assertSee('1 evento')->assertSee('Eleitor liberado');
        $this->get('/admin/auditoria?busca=100%25')->assertSee('Nenhum evento com estes filtros');
    }

    public function test_filtro_invalido_e_ignorado(): void
    {
        $this->actingAs($this->admin)->get('/admin/auditoria?acao=inventada&grupo=x&de=ontem&usuario=abc')
            ->assertOk()->assertSee('2 eventos');
    }

    public function test_historico_da_chapa_pela_lista_de_chapas(): void
    {
        $this->actingAs($this->admin);
        $link = route('admin.auditoria', ['eleicao' => 'todas', 'alvo_tipo' => 'chapa', 'alvo_id' => $this->chapa->id]);

        $this->get('/admin/chapas')->assertSee(e($link), false)->assertSee('Histórico da chapa 20');
        $this->get($link)->assertSee('1 evento')->assertSee('Chapa homologada')->assertSee('Mostrando o histórico de um só registro');
    }

    public function test_detalhes_mostram_o_json_e_o_alvo(): void
    {
        $this->actingAs($this->admin)->get('/admin/auditoria?grupo=chapas')
            ->assertSee('Ver detalhes')
            ->assertSee('&quot;numero&quot;: 20', false)
            ->assertSee($this->chapa->id);
    }

    public function test_pagina_de_50_em_50(): void
    {
        foreach (range(1, 55) as $i) {
            Auditoria::registrar(AcaoAuditoria::Logout, usuario: $this->mesario);
        }

        $this->actingAs($this->admin)->get('/admin/auditoria?eleicao=todas')->assertSee('58 eventos')->assertSee('page=2', false);
        $this->get('/admin/auditoria?eleicao=todas&page=2')->assertOk()->assertSee('Chapa homologada');
    }

    public function test_exporta_o_csv_do_filtro_e_registra_a_exportacao(): void
    {
        $resposta = $this->actingAs($this->admin)->get('/admin/auditoria/exportar.csv?grupo=mesa');

        $resposta->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $resposta->getContent();
        $linhas = array_values(array_filter(explode("\n", $csv)));
        $this->assertCount(2, $linhas, 'Cabeçalho + o único evento da mesa na eleição ativa.');
        $this->assertStringStartsWith("\u{FEFF}Quando;Ação;Código;Quem", $linhas[0]);
        $this->assertStringContainsString('"11/11/2026 10:00:00";"Eleitor liberado";sessao.liberada;"Carlos Mesário (mesário)"', $linhas[1]);
        $this->assertStringContainsString('"Colegiado 2026.2"', $linhas[1]);
        $this->assertStringContainsString('{""matricula"":""2024101"",""terminal"":1}', $linhas[1]);

        $evento = EventoAuditoria::where('acao', AcaoAuditoria::AuditoriaExportada)->sole();
        $this->assertSame(['eleicao' => $this->eleicao->id, 'grupo' => 'mesa'], $evento->dados['filtros']);
        $this->assertSame(hash('sha256', $csv), $evento->dados['sha256']);
        $this->assertSame($this->eleicao->id, $evento->eleicao_id);
    }

    public function test_menu_leva_a_auditoria(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertSee(route('admin.auditoria'));
    }
}
