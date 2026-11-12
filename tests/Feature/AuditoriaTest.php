<?php

namespace Tests\Feature;

use App\Enums\AcaoAuditoria;
use App\Enums\OrigemChapa;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\EventoAuditoria;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\User;
use App\Support\Auditoria;
use App\Support\InscricaoDeChapa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

/** H11 fase 1 · Auditoria de acesso, terminais, sessões e chapas (D22, docs/AUDITORIA.md). */
class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function evento(AcaoAuditoria $acao): EventoAuditoria
    {
        return EventoAuditoria::where('acao', $acao)->sole();
    }

    /** @return Collection<int, string> as ações gravadas, na ordem */
    private function acoes(): Collection
    {
        return EventoAuditoria::orderBy('ocorrido_em')->pluck('acao')->map->value;
    }

    // Princípios ------------------------------------------------------------------------------

    public function test_evento_nao_pode_ser_alterado_nem_apagado(): void
    {
        $evento = Auditoria::registrar(AcaoAuditoria::Login);

        try {
            $evento->update(['ator' => 'outra pessoa']);
            $this->fail('Alterar deveria lançar exceção.');
        } catch (LogicException) {
        }
        $this->expectException(LogicException::class);
        $evento->delete();
    }

    public function test_ordem_preservada_em_microssegundos(): void
    {
        $primeiro = Auditoria::registrar(AcaoAuditoria::Login);
        $segundo = Auditoria::registrar(AcaoAuditoria::Logout);

        $this->assertNotSame('000000', $primeiro->fresh()->ocorrido_em->format('u'));
        $this->assertTrue($segundo->fresh()->ocorrido_em->gt($primeiro->fresh()->ocorrido_em));
    }

    public function test_evento_de_sessao_recusa_a_escolha_do_eleitor(): void
    {
        foreach (['chapa_id', 'chapa', 'voto'] as $proibida) {
            try {
                Auditoria::registrar(AcaoAuditoria::SessaoVotou, dados: [$proibida => 'x']);
                $this->fail("A chave {$proibida} deveria ser recusada.");
            } catch (InvalidArgumentException) {
            }
        }
        $this->assertSame(0, EventoAuditoria::count());
    }

    public function test_acao_desfeita_nao_deixa_evento(): void
    {
        $eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create();
        Cargo::factory()->for($eleicao)->create(['titulo' => 'Presidente', 'ordem' => 1]);
        Eleitor::factory()->create(['matricula' => '2024101']);
        $this->travel(3)->weeks(); // inscrições fecharam

        try {
            app(InscricaoDeChapa::class)->inscrever($eleicao, 'Chapa', [$eleicao->cargos()->value('id') => '2024101'], 'a@b.br', null, OrigemChapa::Formulario);
        } catch (\Throwable) {
        }

        $this->assertSame(0, EventoAuditoria::where('acao', AcaoAuditoria::ChapaInscrita)->count());
    }

    // Acesso ---------------------------------------------------------------------------------

    public function test_login_falha_bloqueio_e_logout(): void
    {
        $maria = User::factory()->create(['email' => 'maria@digitech.local', 'name' => 'Maria', 'perfil' => 'mesario']);

        $this->post('/login', ['email' => 'maria@digitech.local', 'password' => 'errada']);
        $this->post('/login', ['email' => 'ninguem@digitech.local', 'password' => 'x']);
        $this->post('/login', ['email' => 'maria@digitech.local', 'password' => 'password'])->assertRedirect();
        $this->post('/logout');

        $this->assertSame(['acesso.login_falhou', 'acesso.login_falhou', 'acesso.login', 'acesso.logout'], $this->acoes()->all());

        [$senha, $inexistente] = EventoAuditoria::where('acao', AcaoAuditoria::LoginFalhou)->orderBy('ocorrido_em')->get();
        $this->assertSame(['email' => 'maria@digitech.local', 'motivo' => 'senha_incorreta'], $senha->dados);
        $this->assertSame('usuario_inexistente', $inexistente->dados['motivo']);
        $this->assertSame('Visitante', $senha->ator);
        $this->assertSame('127.0.0.1', $senha->ip);
        $this->assertStringNotContainsString('errada', json_encode(EventoAuditoria::all()), 'A senha digitada nunca vai para a auditoria.');

        $login = $this->evento(AcaoAuditoria::Login);
        $this->assertTrue($login->usuario->is($maria));
        $this->assertSame('Maria (mesário)', $login->ator);
    }

    public function test_excesso_de_tentativas_registra_o_bloqueio(): void
    {
        User::factory()->create(['email' => 'maria@digitech.local']);

        foreach (range(1, 6) as $tentativa) {
            $this->post('/login', ['email' => 'maria@digitech.local', 'password' => 'errada']);
        }

        $this->assertSame(['email' => 'maria@digitech.local'], $this->evento(AcaoAuditoria::AcessoBloqueado)->dados);
    }

    // Terminais ------------------------------------------------------------------------------

    public function test_ativacao_e_desativacao_de_terminal(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Comissão']);
        $terminal = Terminal::factory()->create(['numero' => 3]);

        $this->actingAs($admin)->post('/terminal/ativar', ['terminal_id' => $terminal->id]);
        $this->actingAs($admin)->post('/terminal/ativar', ['terminal_id' => $terminal->id]); // outro computador
        $this->actingAs($admin)->post("/admin/terminais/{$terminal->id}/desativar");

        $ativacoes = EventoAuditoria::where('acao', AcaoAuditoria::TerminalAtivado)->orderBy('ocorrido_em')->get();
        $this->assertSame([false, true], $ativacoes->pluck('dados.substituiu_outro_computador')->all());
        $this->assertSame('Comissão (admin)', $ativacoes[0]->ator, 'Registrado antes do logout da ativação.');
        $this->assertSame(['terminal', (string) $terminal->id], [$ativacoes[0]->alvo_tipo, $ativacoes[0]->alvo_id]);
        $this->assertSame(['numero' => 3], $this->evento(AcaoAuditoria::TerminalDesativado)->dados);
    }

    // Sessões --------------------------------------------------------------------------------

    public function test_liberacao_reliberacao_cancelamento_e_recusa(): void
    {
        $mesario = User::factory()->create(['perfil' => 'mesario']);
        $eleicao = Eleicao::factory()->votacaoAberta()->ativa()->create();
        Chapa::factory()->for($eleicao)->homologada()->create();
        $t1 = Terminal::factory()->create(['numero' => 1]);
        $t2 = Terminal::factory()->create(['numero' => 2]);
        $t1->ativar();
        $t2->ativar();
        Eleitor::factory()->create(['matricula' => '2024101']);
        $this->actingAs($mesario);

        $this->post('/mesario/liberar', ['matricula' => '2024101', 'terminal_id' => $t1->id]);
        $this->post('/mesario/liberar', ['matricula' => '2024101', 'terminal_id' => $t2->id]); // já liberado: recusa
        $sessao = SessaoVotacao::sole();
        $this->post("/mesario/sessoes/{$sessao->id}/cancelar");
        $this->post('/mesario/liberar', ['matricula' => '2024101', 'terminal_id' => $t2->id]);

        $this->assertSame(['sessao.liberada', 'sessao.liberacao_recusada', 'sessao.cancelada', 'sessao.reliberada'], $this->acoes()->all());

        $liberada = $this->evento(AcaoAuditoria::SessaoLiberada);
        $this->assertSame(['sessao', $sessao->id, $eleicao->id], [$liberada->alvo_tipo, $liberada->alvo_id, $liberada->eleicao_id]);
        $this->assertSame(['matricula' => '2024101', 'terminal' => 1], array_intersect_key($liberada->dados, array_flip(['matricula', 'terminal'])));

        $recusa = $this->evento(AcaoAuditoria::LiberacaoRecusada);
        $this->assertNull($recusa->alvo_id);
        $this->assertSame(2, $recusa->dados['terminal']);
        $this->assertStringContainsString('liberação em andamento no terminal 1', $recusa->dados['motivo']);

        $this->assertSame(['terminal_anterior' => 1, 'situacao_anterior' => 'cancelada'], array_intersect_key($this->evento(AcaoAuditoria::SessaoReliberada)->dados, array_flip(['terminal_anterior', 'situacao_anterior'])));
        $this->assertSame(1, SessaoVotacao::count(), 'A auditoria guarda o histórico; a sessão continua uma linha só.');
    }

    // Chapas ---------------------------------------------------------------------------------

    public function test_inscricao_pelo_formulario_registra_composicao_e_ip(): void
    {
        $eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create();
        $cargo = Cargo::factory()->for($eleicao)->create(['titulo' => 'Presidente', 'ordem' => 1]);
        Eleitor::factory()->create(['matricula' => '2024101']);

        $this->post('/inscricao', ['nome' => 'Movimento', 'contato_email' => 'a@b.br', 'candidatos' => [$cargo->id => '2024101']], ['REMOTE_ADDR' => '200.1.2.3']);

        $evento = $this->evento(AcaoAuditoria::ChapaInscrita);
        $this->assertSame('Visitante', $evento->ator);
        $this->assertSame('200.1.2.3', $evento->ip);
        $this->assertSame(['numero' => 1, 'nome' => 'Movimento', 'matriculas' => ['Presidente' => '2024101']], $evento->dados);
        $this->assertSame($eleicao->id, $evento->eleicao_id);
    }

    public function test_homologacao_indeferimento_reabertura_troca_e_exclusao(): void
    {
        $admin = User::factory()->admin()->create();
        $eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create();
        $cargo = Cargo::factory()->for($eleicao)->create(['titulo' => 'Presidente', 'ordem' => 1]);
        $chapa = Chapa::factory()->for($eleicao)->create(['numero' => 7]);
        $chapa->candidatos()->create(['eleicao_id' => $eleicao->id, 'cargo_id' => $cargo->id, 'eleitor_id' => Eleitor::factory()->create(['matricula' => '2024101'])->id]);
        Eleitor::factory()->create(['matricula' => '2024102']);
        $this->actingAs($admin);

        $this->post("/admin/chapas/{$chapa->id}/homologar");
        $this->post("/admin/chapas/{$chapa->id}/reabrir");
        $this->post("/admin/chapas/{$chapa->id}/indeferir", ['motivo' => 'Candidato trancou a matrícula.']);
        $this->post("/admin/chapas/{$chapa->id}/reabrir");
        $this->put("/admin/chapas/{$chapa->id}/candidatos/{$cargo->id}", ['matricula' => '2024102']);
        $this->delete("/admin/chapas/{$chapa->id}");

        $this->assertSame(['chapa.homologada', 'chapa.reaberta', 'chapa.indeferida', 'chapa.reaberta', 'chapa.candidato_trocado', 'chapa.excluida'], $this->acoes()->all());
        $this->assertSame('Candidato trancou a matrícula.', $this->evento(AcaoAuditoria::ChapaIndeferida)->dados['motivo']);
        $this->assertSame(['homologada', 'indeferida'], EventoAuditoria::where('acao', AcaoAuditoria::ChapaReaberta)->orderBy('ocorrido_em')->pluck('dados')->pluck('situacao_anterior')->all());
        $this->assertSame(['numero' => 7, 'cargo' => 'Presidente', 'matricula_anterior' => '2024101', 'matricula_nova' => '2024102', 'voltou_para_pendente' => false], $this->evento(AcaoAuditoria::CandidatoTrocado)->dados);

        $excluida = $this->evento(AcaoAuditoria::ChapaExcluida);
        $this->assertSame(['Presidente' => '2024102'], $excluida->dados['matriculas']);
        $this->assertSame((string) $chapa->id, $excluida->alvo_id, 'O evento continua depois que a chapa sumiu.');
        $this->assertTrue(EventoAuditoria::whereNotNull('user_id')->pluck('user_id')->every(fn ($id) => $id === $admin->id));
    }

    public function test_nenhum_evento_tem_a_chapa_de_um_voto(): void
    {
        // Enquanto a H8 não existe: nenhum caminho de sessão grava chapa (a guarda de
        // Auditoria::registrar cobre os demais). Este teste cresce com a H8.
        $this->test_liberacao_reliberacao_cancelamento_e_recusa();

        foreach (EventoAuditoria::where('acao', 'like', 'sessao.%')->get() as $evento) {
            $this->assertEmpty(array_intersect(array_keys($evento->dados), ['chapa', 'chapa_id', 'voto']));
        }
    }
}
