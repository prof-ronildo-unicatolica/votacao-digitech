<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\User;
use App\Support\LiberacaoDeEleitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** H7 · A urna aguarda a liberação e mostra a cédula ao eleitor liberado naquele terminal. */
class UrnaTest extends TestCase
{
    use RefreshDatabase;

    private Eleicao $eleicao;

    private User $mesario;

    /** @var array<int, Terminal> */
    private array $terminal = [];

    /** @var array<int, string> token do cookie de cada terminal */
    private array $token = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mesario = User::factory()->create(['perfil' => 'mesario']);
        $this->eleicao = Eleicao::factory()->votacaoAberta()->ativa()->create(['titulo' => 'Eleição do Colegiado']);

        $presidente = Cargo::factory()->for($this->eleicao)->create(['titulo' => 'Presidente', 'ordem' => 1]);
        $tesoureiro = Cargo::factory()->for($this->eleicao)->create(['titulo' => 'Tesoureiro', 'ordem' => 2]);
        foreach ([[20, 'Movimento Estudantil', 'homologada'], [10, 'Inovação Acadêmica', 'homologada'], [30, 'Chapa Pendente', 'pendente']] as $i => [$numero, $nome, $situacao]) {
            $chapa = Chapa::factory()->for($this->eleicao)->create(['numero' => $numero, 'nome' => $nome, 'situacao' => $situacao]);
            foreach ([$tesoureiro, $presidente] as $j => $cargo) { // fora de ordem de propósito
                $aluno = Eleitor::factory()->create(['nome' => "{$cargo->titulo} da {$numero}"]);
                $chapa->candidatos()->create(['eleicao_id' => $this->eleicao->id, 'cargo_id' => $cargo->id, 'eleitor_id' => $aluno->id]);
            }
        }

        Eleitor::factory()->create(['matricula' => '2024101', 'nome' => 'Maria Oliveira']);
        foreach ([1, 2] as $n) {
            $this->terminal[$n] = Terminal::factory()->create(['numero' => $n]);
            $this->token[$n] = $this->terminal[$n]->ativar();
        }
    }

    private function liberar(string $matricula = '2024101', int $terminal = 1): SessaoVotacao
    {
        return app(LiberacaoDeEleitor::class)->liberar($this->eleicao, $matricula, $this->terminal[$terminal], $this->mesario);
    }

    private function urna(int $terminal = 1): TestResponse
    {
        return $this->withCookie(Terminal::COOKIE, $this->token[$terminal])->get('/urna');
    }

    private function consultarStatus(int $terminal = 1, ?string $token = null): TestResponse
    {
        return $this->withCredentials()->withCookie(Terminal::COOKIE, $token ?? $this->token[$terminal])->getJson('/urna/status');
    }

    public function test_status_muda_de_falso_para_verdadeiro_apos_a_liberacao(): void
    {
        $this->consultarStatus()->assertOk()->assertExactJson(['terminal' => 1, 'liberada' => false, 'versao' => null]);
        $this->urna()->assertOk()->assertSee('Aguardando liberação')->assertSee('setTimeout(consultar, 3000)', false);

        $sessao = $this->liberar();

        $this->consultarStatus()->assertOk()->assertExactJson(['terminal' => 1, 'liberada' => true, 'versao' => $sessao->versao()]);
    }

    public function test_cedula_mostra_o_nome_e_so_as_chapas_homologadas_com_a_composicao(): void
    {
        $this->liberar();

        $this->urna()->assertOk()
            ->assertSee('Olá, Maria Oliveira')
            ->assertSeeInOrder(['Inovação Acadêmica', 'Presidente: Presidente da 10 · Tesoureiro: Tesoureiro da 10', 'Movimento Estudantil', 'Branco', 'Nulo'])
            ->assertDontSee('Chapa Pendente')
            ->assertDontSee('dt-menu-lateral', false);
    }

    public function test_status_nao_devolve_o_nome_do_eleitor(): void
    {
        $this->liberar();

        $this->assertStringNotContainsString('Maria', $this->consultarStatus()->getContent());
        $this->assertStringNotContainsString('2024101', $this->consultarStatus()->getContent());
    }

    public function test_sem_cookie_ou_com_cookie_invalido_responde_403(): void
    {
        $this->liberar();

        $this->get('/urna')->assertForbidden()->assertSee('Este computador não está ativado como terminal. Chame o mesário.')->assertDontSee('Maria');
        $this->getJson('/urna/status')->assertForbidden()->assertJsonMissing(['liberada' => true]);
        $this->consultarStatus(token: str_repeat('x', 64))->assertForbidden();
    }

    public function test_cookie_de_outro_terminal_nao_ve_a_liberacao_deste(): void
    {
        $this->liberar(terminal: 1);

        $this->consultarStatus(2)->assertOk()->assertJson(['terminal' => 2, 'liberada' => false]);
        $this->urna(2)->assertOk()->assertSee('Aguardando liberação')->assertDontSee('Maria Oliveira');
    }

    public function test_cookie_antigo_apos_reativar_em_outro_computador_responde_403(): void
    {
        $antigo = $this->token[1];
        $this->terminal[1]->ativar(); // outro computador pareou como terminal 1

        $this->liberar();

        $this->consultarStatus(token: $antigo)->assertForbidden();
        $this->withCookie(Terminal::COOKIE, $antigo)->get('/urna')->assertForbidden()->assertDontSee('Maria');
    }

    public function test_cedula_aparecer_muda_o_terminal_para_votando(): void
    {
        $sessao = $this->liberar();
        $this->assertSame('liberado', $this->terminal[1]->fresh()->estado());

        $this->urna()->assertOk();

        $this->assertNotNull($sessao->fresh()->exibida_em);
        $this->assertSame('votando', $this->terminal[1]->fresh()->estado());
        $this->actingAs($this->mesario)->get('/mesario')->assertSee('Maria Oliveira na cabine');
    }

    public function test_sessao_expirada_ou_cancelada_volta_para_aguardando(): void
    {
        $sessao = $this->liberar();
        $versao = $sessao->versao();

        $this->travel(31)->minutes();
        $this->consultarStatus()->assertJson(['liberada' => false]);
        $this->urna()->assertSee('Aguardando liberação');

        // Liberada de novo (outro terminal, depois cancelada e liberada no 1): a versão muda.
        $sessao = $this->liberar();
        app(LiberacaoDeEleitor::class)->cancelar($sessao);
        $this->urna()->assertSee('Aguardando liberação');
        $nova = $this->liberar();
        $this->assertNotSame($versao, $nova->versao());
        $this->consultarStatus()->assertJson(['liberada' => true, 'versao' => $nova->versao()]);
    }

    public function test_votacao_encerrada_nao_mostra_a_cedula(): void
    {
        $this->liberar();
        $this->eleicao->update(['votacao_fecha_em' => now()->subMinute()]);

        $this->consultarStatus()->assertJson(['liberada' => false]);
        $this->urna()->assertSee('Aguardando liberação')->assertDontSee('Maria Oliveira');
    }
}
