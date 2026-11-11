<?php

namespace Tests\Feature;

use App\Enums\AcaoAuditoria;
use App\Enums\SituacaoSessao;
use App\Enums\TipoVoto;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\EventoAuditoria;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\User;
use App\Models\Voto;
use App\Support\LiberacaoDeEleitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** H8 · O eleitor escolhe, confirma e vota; o voto não liga ao eleitor (D3, D24). */
class VotoTest extends TestCase
{
    use RefreshDatabase;

    private Eleicao $eleicao;

    private User $mesario;

    private Chapa $chapa;

    /** @var array<int, Terminal> */
    private array $terminal = [];

    /** @var array<int, string> */
    private array $token = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mesario = User::factory()->create(['perfil' => 'mesario']);
        $this->eleicao = Eleicao::factory()->votacaoAberta()->ativa()->create();
        $cargo = Cargo::factory()->for($this->eleicao)->create(['titulo' => 'Presidente', 'ordem' => 1]);
        $this->chapa = Chapa::factory()->for($this->eleicao)->homologada()->create(['numero' => 20, 'nome' => 'Movimento Estudantil']);
        $this->chapa->candidatos()->create(['eleicao_id' => $this->eleicao->id, 'cargo_id' => $cargo->id, 'eleitor_id' => Eleitor::factory()->create(['nome' => 'Carla Dias'])->id]);

        Eleitor::factory()->create(['matricula' => '2024101', 'nome' => 'Maria Oliveira']);
        Eleitor::factory()->create(['matricula' => '2024102', 'nome' => 'João Silva']);
        foreach ([1, 2] as $n) {
            $this->terminal[$n] = Terminal::factory()->create(['numero' => $n]);
            $this->token[$n] = $this->terminal[$n]->ativar();
        }
    }

    private function liberar(string $matricula = '2024101', int $terminal = 1): SessaoVotacao
    {
        return app(LiberacaoDeEleitor::class)->liberar($this->eleicao, $matricula, $this->terminal[$terminal], $this->mesario);
    }

    private function votar(SessaoVotacao $sessao, string $escolha, int $terminal = 1): TestResponse
    {
        return $this->withCookie(Terminal::COOKIE, $this->token[$terminal])
            ->post('/urna/votar', ['versao' => $sessao->versao(), 'escolha' => $escolha]);
    }

    public function test_cedula_confirmacao_e_voto_valido_em_chapa(): void
    {
        $sessao = $this->liberar();

        $this->withCookie(Terminal::COOKIE, $this->token[1])->get('/urna')->assertSee('name="escolha"', false)->assertSee(route('urna.confirmar'));
        $this->withCookie(Terminal::COOKIE, $this->token[1])->post('/urna/confirmar', ['versao' => $sessao->versao(), 'escolha' => $this->chapa->id])
            ->assertOk()->assertSee('Confirme seu voto')->assertSee('Movimento Estudantil')->assertSee('Presidente: Carla Dias')->assertSee('Confirmar voto');
        $this->assertSame(0, Voto::count(), 'Confirmar não grava nada.');

        $this->votar($sessao, $this->chapa->id)->assertRedirect('/urna/registrado');

        $voto = Voto::sole();
        $this->assertSame(TipoVoto::Chapa, $voto->tipo);
        $this->assertSame($this->chapa->id, $voto->chapa_id);
        $this->assertSame($this->eleicao->id, $voto->eleicao_id);
        $this->assertSame(SituacaoSessao::Votou, $sessao->fresh()->situacao);
        $this->assertNotNull($sessao->fresh()->votou_em);
        $this->assertSame('livre', $this->terminal[1]->fresh()->estado());

        $this->withCookie(Terminal::COOKIE, $this->token[1])->get('/urna/registrado')
            ->assertOk()->assertSee('Voto registrado')->assertSee('http-equiv="refresh" content="5;', false)->assertDontSee('Movimento');
    }

    public function test_voto_em_branco_e_nulo(): void
    {
        $this->votar($this->liberar('2024101'), 'branco')->assertRedirect('/urna/registrado');
        $this->votar($this->liberar('2024102', 2), 'nulo', 2)->assertRedirect('/urna/registrado');

        $this->assertSame([['branco', null], ['nulo', null]], Voto::orderBy('tipo')->get()->map(fn ($v) => [$v->tipo->value, $v->chapa_id])->all());
    }

    public function test_segundo_envio_nao_gera_segundo_voto(): void
    {
        $sessao = $this->liberar();

        $this->votar($sessao, $this->chapa->id)->assertRedirect('/urna/registrado');
        $this->votar($sessao, 'nulo')->assertRedirect('/urna/registrado'); // duplo clique, F5, outra aba

        $this->assertSame(1, Voto::count());
        $this->assertSame(TipoVoto::Chapa, Voto::sole()->tipo, 'O segundo envio não troca o voto.');
    }

    public function test_sessao_expirada_ou_cancelada_e_rejeitada(): void
    {
        $sessao = $this->liberar();
        $this->travel(31)->minutes();
        $this->votar($sessao, $this->chapa->id)->assertRedirect('/urna')->assertSessionHas('aviso');

        $sessao = $this->liberar(); // reliberada (a anterior expirou)
        app(LiberacaoDeEleitor::class)->cancelar($sessao);
        $this->votar($sessao, $this->chapa->id)->assertRedirect('/urna');

        $this->assertSame(0, Voto::count());
    }

    public function test_chapa_de_outra_eleicao_ou_nao_homologada_e_rejeitada(): void
    {
        $sessao = $this->liberar();
        $outra = Chapa::factory()->homologada()->create(); // outra eleição
        $pendente = Chapa::factory()->for($this->eleicao)->create(['numero' => 30]);

        $this->votar($sessao, $outra->id)->assertRedirect('/urna')->assertSessionHas('aviso', 'Esta opção não está na cédula. Escolha de novo.');
        $this->votar($sessao, $pendente->id)->assertRedirect('/urna');
        $this->votar($sessao, 'qualquer-coisa')->assertRedirect('/urna');

        $this->assertSame(0, Voto::count());
        $this->assertSame(SituacaoSessao::Aberta, $sessao->fresh()->situacao, 'Opção inválida não consome a liberação.');
    }

    public function test_voto_de_outro_terminal_ou_sem_cookie_e_rejeitado(): void
    {
        $sessao = $this->liberar(terminal: 1);

        // Sem cookie primeiro: withCookie() vale para as requisições seguintes do mesmo teste.
        $this->post('/urna/votar', ['versao' => $sessao->versao(), 'escolha' => $this->chapa->id])->assertForbidden();
        $this->votar($sessao, $this->chapa->id, terminal: 2)->assertRedirect('/urna');

        $this->assertSame(0, Voto::count());
        $this->assertSame(SituacaoSessao::Aberta, $sessao->fresh()->situacao);
    }

    public function test_confirmacao_antiga_nao_vale_para_outra_liberacao_no_mesmo_terminal(): void
    {
        $maria = $this->liberar('2024101');
        $versaoDaMaria = $maria->versao();
        app(LiberacaoDeEleitor::class)->cancelar($maria);
        $joao = $this->liberar('2024102'); // mesmo terminal

        $this->withCookie(Terminal::COOKIE, $this->token[1])
            ->post('/urna/votar', ['versao' => $versaoDaMaria, 'escolha' => $this->chapa->id])->assertRedirect('/urna');

        $this->assertSame(0, Voto::count());
        $this->assertSame(SituacaoSessao::Aberta, $joao->fresh()->situacao, 'A liberação do João continua intacta.');
    }

    public function test_eleicao_do_voto_vem_da_sessao_e_nao_do_formulario(): void
    {
        $outra = Eleicao::factory()->create();
        $sessao = $this->liberar();

        $this->withCookie(Terminal::COOKIE, $this->token[1])
            ->post('/urna/votar', ['versao' => $sessao->versao(), 'escolha' => 'branco', 'eleicao_id' => $outra->id]);

        $this->assertSame($this->eleicao->id, Voto::sole()->eleicao_id);
    }

    public function test_votacao_encerrada_rejeita_o_voto(): void
    {
        $sessao = $this->liberar();
        $this->eleicao->update(['votacao_fecha_em' => now()->subMinute()]);

        $this->votar($sessao, $this->chapa->id)->assertRedirect('/urna');
        $this->assertSame(0, Voto::count());
    }

    public function test_sigilo_nada_liga_a_escolha_ao_eleitor(): void
    {
        $sessao = $this->liberar();
        $resposta = $this->votar($sessao, $this->chapa->id);

        // Sessão HTTP e flash: a escolha não fica guardada em lugar nenhum.
        $this->assertStringNotContainsString($this->chapa->id, json_encode(session()->all()));
        $this->assertStringNotContainsString($this->chapa->id, (string) $resposta->headers->get('Location'));

        // Auditoria: "a sessão votou", sem a chapa; um evento por sessão que votou.
        $evento = EventoAuditoria::where('acao', AcaoAuditoria::SessaoVotou)->sole();
        $this->assertSame(['terminal' => 1], $evento->dados);
        $this->assertSame($sessao->id, $evento->alvo_id);
        $this->assertStringNotContainsString($this->chapa->id, json_encode(EventoAuditoria::all()->toArray()));
        $this->assertSame(SessaoVotacao::where('situacao', 'votou')->count(), EventoAuditoria::where('acao', AcaoAuditoria::SessaoVotou)->count());

        // O voto: sem eleitor, terminal, sessão ou horário.
        $this->assertSame(['id', 'eleicao_id', 'chapa_id', 'tipo'], array_keys(Voto::sole()->getAttributes()));
    }
}
