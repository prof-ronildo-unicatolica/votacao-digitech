<?php

namespace Tests\Feature;

use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\Terminal;
use App\Models\User;
use App\Support\LiberacaoDeEleitor;
use App\Support\RegistroDeVoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** H9 · O painel da mesa se atualiza sozinho: terminais e comparecimento, nunca votos por chapa. */
class PainelMesaTest extends TestCase
{
    use RefreshDatabase;

    private User $mesario;

    private Eleicao $eleicao;

    /** @var array<int, Terminal> */
    private array $terminal = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mesario = User::factory()->create(['perfil' => 'mesario']);
        $this->actingAs($this->mesario);
        $this->eleicao = Eleicao::factory()->votacaoAberta()->ativa()->create();
        Chapa::factory()->for($this->eleicao)->homologada()->create(['numero' => 10, 'nome' => 'Chapa Secreta']);
        foreach ([1, 2, 3] as $n) {
            $this->terminal[$n] = Terminal::factory()->create(['numero' => $n]);
        }
        $this->terminal[1]->ativar();
        $this->terminal[2]->ativar(); // o 3 fica inativo
        Eleitor::factory()->create(['matricula' => '2024101', 'nome' => 'Maria Oliveira']);
        Eleitor::factory()->count(3)->create();
    }

    private function estados(): array
    {
        return collect($this->getJson('/mesario/painel')->assertOk()->json('terminais'))->pluck('estado', 'numero')->all();
    }

    public function test_painel_acompanha_livre_liberado_votando_e_livre_de_novo(): void
    {
        $this->assertSame([1 => 'livre', 2 => 'livre', 3 => 'inativo'], $this->estados());

        $sessao = app(LiberacaoDeEleitor::class)->liberar($this->eleicao, '2024101', $this->terminal[1], $this->mesario);
        $this->assertSame('liberado', $this->estados()[1]);
        $this->getJson('/mesario/painel')->assertJsonPath('terminais_html', fn (string $html) => str_contains($html, 'Maria Oliveira · expira em 30 min') && str_contains($html, 'Cancelar sessão'));

        $sessao->update(['exibida_em' => now()]); // a cédula apareceu na urna (H7)
        $this->assertSame('votando', $this->estados()[1]);
        $this->getJson('/mesario/painel')->assertJsonPath('terminais_html', fn (string $html) => str_contains($html, 'Maria Oliveira na cabine'));

        app(RegistroDeVoto::class)->votar($this->terminal[1], $sessao->versao(), 'branco');
        $this->assertSame('livre', $this->estados()[1]);
    }

    public function test_comparecimento_sem_votos_por_chapa(): void
    {
        $sessao = app(LiberacaoDeEleitor::class)->liberar($this->eleicao, '2024101', $this->terminal[1], $this->mesario);
        app(RegistroDeVoto::class)->votar($this->terminal[1], $sessao->versao(), Chapa::sole()->id);

        $painel = $this->getJson('/mesario/painel')->assertOk();

        $this->assertStringContainsString('de 4 aptos · 25%', $painel->json('indicadores'));
        $this->assertStringNotContainsString('Chapa Secreta', $painel->getContent());
        $this->assertStringNotContainsString('Chapa 10', $painel->getContent());
        $this->get('/mesario')->assertDontSee('Chapa Secreta');
    }

    public function test_terminal_inativo_nao_pode_ser_escolhido(): void
    {
        $html = $this->get('/mesario?matricula=2024101')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="'.$this->terminal[3]->id.'"[^>]*disabled[^>]*>\s*Terminal 3 · Inativo/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="'.$this->terminal[1]->id.'"[^>]*disabled/', $html);
    }

    public function test_pagina_traz_o_script_de_atualizacao(): void
    {
        $this->get('/mesario')->assertOk()
            ->assertSee(str_replace('/', '\\/', route('mesario.painel')), false)
            ->assertSee('setInterval(atualizar, 5000)', false)
            ->assertSee('id="painel-terminais"', false);
    }

    public function test_sem_login_o_painel_responde_401(): void
    {
        auth()->logout();

        $this->getJson('/mesario/painel')->assertUnauthorized();
    }
}
