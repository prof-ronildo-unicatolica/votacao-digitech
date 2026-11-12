<?php

namespace Tests\Feature;

use App\Enums\AcaoAuditoria;
use App\Models\Cargo;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\EventoAuditoria;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** H11 fase 2 · Auditoria dos cadastros e das importações (D22, docs/AUDITORIA.md). */
class AuditoriaCadastrosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
        $this->actingAs($this->admin);
    }

    private function evento(AcaoAuditoria $acao): EventoAuditoria
    {
        return EventoAuditoria::where('acao', $acao)->sole();
    }

    private function semEvento(AcaoAuditoria $acao): void
    {
        $this->assertSame(0, EventoAuditoria::where('acao', $acao)->count(), "Não deveria haver {$acao->value}.");
    }

    /** Quem fez, de onde e sobre o quê: vale para todo evento de cadastro. */
    private function assertAtorEAlvo(EventoAuditoria $evento, string $tipo, string $id): void
    {
        $this->assertSame($this->admin->id, $evento->user_id);
        $this->assertSame('Ana Admin (admin)', $evento->ator);
        $this->assertSame('127.0.0.1', $evento->ip);
        $this->assertSame($tipo, $evento->alvo_tipo);
        $this->assertSame($id, $evento->alvo_id);
    }

    // Eleição ---------------------------------------------------------------------------------

    private function dadosEleicao(array $troca = []): array
    {
        return $troca + [
            'titulo' => 'Centro Acadêmico 2026.2',
            'inscricao_abre_em' => '2026-11-10T08:00',
            'inscricao_fecha_em' => '2026-11-20T18:00',
            'votacao_abre_em' => '2026-11-25T08:00',
            'votacao_fecha_em' => '2026-11-25T18:00',
        ];
    }

    public function test_eleicao_criada_ja_ativa_e_alterada_com_antes_e_depois(): void
    {
        $this->post('/admin/eleicoes', $this->dadosEleicao())->assertRedirect('/admin/eleicoes');
        $eleicao = Eleicao::sole();

        $criada = $this->evento(AcaoAuditoria::EleicaoCriada);
        $this->assertAtorEAlvo($criada, 'eleicao', $eleicao->id);
        $this->assertSame($eleicao->id, $criada->eleicao_id);
        $this->assertSame('Centro Acadêmico 2026.2', $criada->dados['titulo']);
        $this->assertSame($eleicao->votacao_fecha_em->toIso8601String(), $criada->dados['votacao_fecha_em']);
        $this->assertSame(['titulo' => 'Centro Acadêmico 2026.2'], $this->evento(AcaoAuditoria::EleicaoAtivada)->dados);

        $this->put("/admin/eleicoes/{$eleicao->id}", $this->dadosEleicao(['titulo' => 'CA 2026.2', 'votacao_fecha_em' => '2026-11-25T20:00']));

        $alterada = $this->evento(AcaoAuditoria::EleicaoAlterada)->dados;
        $this->assertSame(['titulo', 'votacao_fecha_em'], array_keys($alterada));
        $this->assertSame(['antes' => 'Centro Acadêmico 2026.2', 'depois' => 'CA 2026.2'], $alterada['titulo']);
        $this->assertStringStartsWith('2026-11-25T18:00:00', $alterada['votacao_fecha_em']['antes']);
        $this->assertStringStartsWith('2026-11-25T20:00:00', $alterada['votacao_fecha_em']['depois']);
    }

    public function test_salvar_sem_mudar_nada_nao_gera_evento(): void
    {
        $this->post('/admin/eleicoes', $this->dadosEleicao());
        $this->put('/admin/eleicoes/'.Eleicao::sole()->id, $this->dadosEleicao());

        $this->semEvento(AcaoAuditoria::EleicaoAlterada);
    }

    public function test_ativar_outra_eleicao_registra_quem_saiu_e_quem_entrou(): void
    {
        $antiga = Eleicao::factory()->ativa()->create(['titulo' => 'Antiga']);
        $nova = Eleicao::factory()->create(['titulo' => 'Nova']);

        $this->post("/admin/eleicoes/{$nova->id}/ativar");

        $this->assertSame($antiga->id, $this->evento(AcaoAuditoria::EleicaoDesativada)->alvo_id);
        $this->assertSame($nova->id, $this->evento(AcaoAuditoria::EleicaoAtivada)->alvo_id);

        $this->post("/admin/eleicoes/{$nova->id}/desativar");
        $this->assertSame(2, EventoAuditoria::where('acao', AcaoAuditoria::EleicaoDesativada)->count());
    }

    public function test_eleicao_excluida_guarda_o_titulo_mesmo_depois_de_apagada(): void
    {
        $eleicao = Eleicao::factory()->create(['titulo' => 'Cadastrada errada']);

        $this->delete("/admin/eleicoes/{$eleicao->id}")->assertRedirect('/admin/eleicoes');

        $evento = $this->evento(AcaoAuditoria::EleicaoExcluida);
        $this->assertSame('Cadastrada errada', $evento->dados['titulo']);
        $this->assertSame($eleicao->id, $evento->alvo_id);
        $this->assertNull($evento->eleicao_id, 'A eleição sumiu: o banco anula o vínculo, o alvo continua.');
    }

    public function test_exclusao_recusada_nao_gera_evento(): void
    {
        $eleicao = Eleicao::factory()->create();
        $eleicao->chapas()->create(['numero' => 1, 'nome' => 'Chapa']);

        $this->delete("/admin/eleicoes/{$eleicao->id}")->assertSessionHasErrors('geral');

        $this->semEvento(AcaoAuditoria::EleicaoExcluida);
    }

    // Cargos ----------------------------------------------------------------------------------

    public function test_cargos_criado_renomeado_movido_removido_e_copiados(): void
    {
        $eleicao = Eleicao::factory()->create();
        $base = "/admin/eleicoes/{$eleicao->id}/cargos";

        $this->post($base, ['titulo' => 'Presidente']);
        $this->post($base, ['titulo' => 'Tesoureiro']);
        $presidente = Cargo::where('titulo', 'Presidente')->sole();

        $criado = EventoAuditoria::where('acao', AcaoAuditoria::CargoCriado)->orderBy('ocorrido_em')->first();
        $this->assertAtorEAlvo($criado, 'cargo', $presidente->id);
        $this->assertSame($eleicao->id, $criado->eleicao_id);
        $this->assertSame(['titulo' => 'Presidente', 'ordem' => 1], $criado->dados);

        $this->put("{$base}/{$presidente->id}", ["titulo_{$presidente->id}" => 'Presidência']);
        $this->assertSame(['titulo' => ['antes' => 'Presidente', 'depois' => 'Presidência'], 'ordem' => 1], $this->evento(AcaoAuditoria::CargoRenomeado)->dados);

        $this->post("{$base}/{$presidente->id}/mover", ['direcao' => 'baixo']);
        $this->assertSame(['titulo' => 'Presidência', 'ordem' => ['antes' => 1, 'depois' => 2]], $this->evento(AcaoAuditoria::CargoMovido)->dados);

        $this->delete("{$base}/{$presidente->id}");
        $this->assertSame(['titulo' => 'Presidência', 'ordem' => 2], $this->evento(AcaoAuditoria::CargoRemovido)->dados);

        $destino = Eleicao::factory()->create();
        $this->post("/admin/eleicoes/{$destino->id}/cargos/copiar", ['origem_id' => $eleicao->id]);
        $copia = $this->evento(AcaoAuditoria::CargosCopiados);
        $this->assertSame($destino->id, $copia->eleicao_id);
        $this->assertSame(1, $copia->dados['quantidade']);
        $this->assertSame(['Tesoureiro'], $copia->dados['titulos']);
    }

    public function test_mover_na_ponta_e_alteracao_bloqueada_nao_geram_evento(): void
    {
        $eleicao = Eleicao::factory()->create();
        $cargo = Cargo::factory()->for($eleicao)->create(['titulo' => 'Presidente', 'ordem' => 1]);

        $this->post("/admin/eleicoes/{$eleicao->id}/cargos/{$cargo->id}/mover", ['direcao' => 'cima']);
        $this->semEvento(AcaoAuditoria::CargoMovido);

        $eleicao->chapas()->create(['numero' => 1, 'nome' => 'Chapa']);
        $this->post("/admin/eleicoes/{$eleicao->id}/cargos", ['titulo' => 'Vice'])->assertSessionHasErrors('geral');
        $this->semEvento(AcaoAuditoria::CargoCriado);
    }

    // Eleitores -------------------------------------------------------------------------------

    public function test_eleitor_criado_alterado_e_excluido(): void
    {
        $this->post('/admin/eleitores', ['matricula' => '2024001', 'nome' => 'Ana Lima', 'turma' => 'ADS 3', 'email' => '']);
        $eleitor = Eleitor::sole();

        $criado = $this->evento(AcaoAuditoria::EleitorCriado);
        $this->assertAtorEAlvo($criado, 'eleitor', $eleitor->id);
        $this->assertSame(['matricula' => '2024001'], $criado->dados, 'Dado pessoal mínimo: só a matrícula.');
        $this->assertNull($criado->eleicao_id);

        $this->put("/admin/eleitores/{$eleitor->id}", ['matricula' => '2024001', 'nome' => 'Ana Lima', 'turma' => 'ADS 4', 'email' => '']);
        $this->assertSame(['matricula' => '2024001', 'campos' => ['turma' => ['antes' => 'ADS 3', 'depois' => 'ADS 4']]], $this->evento(AcaoAuditoria::EleitorAlterado)->dados);

        $this->delete("/admin/eleitores/{$eleitor->id}");
        $this->assertSame(['matricula' => '2024001'], $this->evento(AcaoAuditoria::EleitorExcluido)->dados);
    }

    // Terminais -------------------------------------------------------------------------------

    public function test_terminal_criado_alterado_e_excluido_sem_o_token(): void
    {
        // Como o navegador manda: texto. O evento guarda o número como número.
        $this->post('/admin/terminais', ['numero' => '3', 'local' => 'Laboratório 2']);
        $terminal = Terminal::sole();
        $this->assertSame(['numero' => 3, 'local' => 'Laboratório 2'], $this->evento(AcaoAuditoria::TerminalCriado)->dados);

        $terminal->ativar();
        $this->put("/admin/terminais/{$terminal->id}", ['numero' => 3, 'local' => 'Biblioteca']);
        $this->assertSame(['numero' => 3, 'campos' => ['local' => ['antes' => 'Laboratório 2', 'depois' => 'Biblioteca']]], $this->evento(AcaoAuditoria::TerminalAlterado)->dados);

        $this->delete("/admin/terminais/{$terminal->id}");
        $this->assertSame(['numero' => 3, 'local' => 'Biblioteca'], $this->evento(AcaoAuditoria::TerminalExcluido)->dados);

        $this->assertStringNotContainsString('token', EventoAuditoria::pluck('dados')->toJson());
    }

    // Usuários --------------------------------------------------------------------------------

    public function test_usuario_criado_alterado_desativado_reativado_e_excluido_sem_senha(): void
    {
        $this->post('/admin/usuarios', ['name' => 'Maria Mesária', 'email' => 'maria@digitech.local', 'perfil' => 'mesario', 'password' => 'segredo123', 'password_confirmation' => 'segredo123']);
        $maria = User::where('email', 'maria@digitech.local')->sole();
        $criado = $this->evento(AcaoAuditoria::UsuarioCriado);
        $this->assertAtorEAlvo($criado, 'usuario', (string) $maria->id);
        $this->assertSame(['email' => 'maria@digitech.local', 'perfil' => 'mesario'], $criado->dados);

        $this->put("/admin/usuarios/{$maria->id}", ['name' => 'Maria Souza', 'email' => 'maria@digitech.local', 'perfil' => 'mesario', 'password' => 'outrasenha9', 'password_confirmation' => 'outrasenha9']);
        $alterado = $this->evento(AcaoAuditoria::UsuarioAlterado)->dados;
        $this->assertSame(['name' => ['antes' => 'Maria Mesária', 'depois' => 'Maria Souza']], $alterado['campos']);
        $this->assertTrue($alterado['senha_trocada']);

        $this->post("/admin/usuarios/{$maria->id}/ativo");
        $this->post("/admin/usuarios/{$maria->id}/ativo");
        $this->assertSame(1, EventoAuditoria::where('acao', AcaoAuditoria::UsuarioDesativado)->count());
        $this->assertSame(1, EventoAuditoria::where('acao', AcaoAuditoria::UsuarioReativado)->count());

        $this->delete("/admin/usuarios/{$maria->id}")->assertRedirect('/admin/usuarios');
        $excluido = $this->evento(AcaoAuditoria::UsuarioExcluido);
        $this->assertSame((string) $maria->id, $excluido->alvo_id);
        $this->assertSame($this->admin->id, $excluido->user_id, 'O ator continua sendo quem excluiu.');

        $json = EventoAuditoria::pluck('dados')->toJson();
        foreach (['segredo123', 'outrasenha9', 'password', '$2y$'] as $proibido) {
            $this->assertStringNotContainsString($proibido, $json);
        }
    }

    // Importações -----------------------------------------------------------------------------

    private function conferir(string $rota, string $arquivo, string $conteudo): string
    {
        $this->post("/admin/{$rota}/importar", ['arquivo' => UploadedFile::fake()->createWithContent($arquivo, $conteudo)])->assertRedirect("/admin/{$rota}/importar/conferir");
        preg_match('/name="assinatura" value="([0-9a-f]+)"/', $this->get("/admin/{$rota}/importar/conferir")->getContent(), $m);

        return $m[1];
    }

    private function confirmar(string $rota, string $assinatura, string $acao): TestResponse
    {
        return $this->post("/admin/{$rota}/importar/confirmar", ['assinatura' => $assinatura, 'acao' => $acao]);
    }

    public function test_eleitores_importados_com_nome_e_hash_do_arquivo(): void
    {
        $csv = file_get_contents(base_path('tests/Fixtures/eleitores/com-erros.csv'));
        $assinatura = $this->conferir('eleitores', 'turma-ads.csv', $csv);
        $this->semEvento(AcaoAuditoria::EleitoresImportados);

        $this->confirmar('eleitores', $assinatura, 'ignorar')->assertRedirect('/admin/eleitores/importar');

        $evento = $this->evento(AcaoAuditoria::EleitoresImportados);
        $this->assertSame('turma-ads.csv', $evento->dados['arquivo']);
        $this->assertSame(hash('sha256', $csv), $evento->dados['sha256']);
        $this->assertSame(Eleitor::count(), $evento->dados['criados']);
        $this->assertGreaterThan(0, $evento->dados['ignoradas']);
        $this->assertSame(0, EventoAuditoria::where('acao', AcaoAuditoria::EleitorCriado)->count(), 'A importação é um evento só.');
    }

    public function test_importacao_cancelada_e_importacao_desfeita(): void
    {
        $csv = file_get_contents(base_path('tests/Fixtures/eleitores/limpo.csv'));
        $this->conferir('eleitores', 'eleitores.csv', $csv);

        $this->post('/admin/eleitores/importar/confirmar', ['acao' => 'cancelar']);
        $this->assertSame(['tipo' => 'eleitores', 'arquivo' => 'eleitores.csv', 'sha256' => hash('sha256', $csv)], $this->evento(AcaoAuditoria::ImportacaoCancelada)->dados);

        // Assinatura velha: a importação é desfeita, e o evento some com ela.
        $this->conferir('eleitores', 'eleitores.csv', $csv);
        $this->confirmar('eleitores', str_repeat('0', 64), 'importar');
        $this->assertSame(0, Eleitor::count());
        $this->semEvento(AcaoAuditoria::EleitoresImportados);
    }

    public function test_chapas_importadas_com_numeros_e_cargos_criados(): void
    {
        $eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create();
        foreach (range(101, 112) as $n) {
            Eleitor::factory()->create(['matricula' => "2024{$n}"]);
        }
        $csv = file_get_contents(base_path('tests/Fixtures/chapas/limpo.csv'));

        $this->confirmar('chapas', $this->conferir('chapas', 'chapas.csv', $csv), 'importar')->assertRedirect('/admin/chapas/importar');

        $evento = $this->evento(AcaoAuditoria::ChapasImportadas);
        $this->assertSame($eleicao->id, $evento->eleicao_id);
        $this->assertSame('chapas.csv', $evento->dados['arquivo']);
        $this->assertSame(hash('sha256', $csv), $evento->dados['sha256']);
        $this->assertSame($eleicao->chapas()->pluck('numero')->all(), $evento->dados['numeros']);
        $this->assertSame($eleicao->cargos()->pluck('titulo')->all(), $evento->dados['cargos_criados']);
        $this->semEvento(AcaoAuditoria::ChapaInscrita);

        $this->conferir('chapas', 'outra.csv', $csv);
        $this->post('/admin/chapas/importar/confirmar', ['acao' => 'cancelar']);
        $cancelada = $this->evento(AcaoAuditoria::ImportacaoCancelada);
        $this->assertSame('chapas', $cancelada->dados['tipo']);
        $this->assertSame($eleicao->id, $cancelada->eleicao_id);
    }

    // Catálogo ------------------------------------------------------------------------------

    public function test_todo_caso_do_catalogo_tem_rotulo_e_esta_no_documento(): void
    {
        $documento = file_get_contents(base_path('docs/AUDITORIA.md'));

        foreach (AcaoAuditoria::cases() as $acao) {
            $this->assertNotSame('', $acao->rotulo());
            $this->assertStringContainsString($acao->categoria(), $acao->value);
            $sufixo = substr($acao->value, strlen($acao->categoria()) + 1);
            $this->assertTrue(
                str_contains($documento, "`{$acao->value}`") || str_contains($documento, "`.{$sufixo}`"),
                "{$acao->value} não está no catálogo de docs/AUDITORIA.md.",
            );
        }
    }
}
