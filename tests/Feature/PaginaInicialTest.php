<?php

namespace Tests\Feature;

use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EXEMPLO de TESTE DE INTEGRAÇÃO (Laravel chama de "Feature test"): sobe a aplicação,
 * usa um banco real (SQLite em memória, ver phpunit.xml) e faz requisições HTTP.
 * Aqui: a página inicial pública mostra a eleição ativa, a fase e as chapas homologadas.
 */
class PaginaInicialTest extends TestCase
{
    use RefreshDatabase;

    public function test_sem_eleicao_ativa_avisa_e_mostra_o_acesso(): void
    {
        Eleicao::factory()->inscricoesAbertas()->create(['titulo' => 'Eleição inativa']);

        $this->get('/')->assertOk()
            ->assertSee('Nenhuma eleição em andamento')
            ->assertDontSee('Eleição inativa')
            ->assertSee(route('login'), false);
    }

    public function test_lista_so_as_chapas_homologadas_da_eleicao_ativa_com_a_composicao(): void
    {
        $eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create();
        $cargos = collect(['Presidente', 'Tesoureiro'])->map(fn ($t, $i) => Cargo::factory()->for($eleicao)->create(['titulo' => $t, 'ordem' => $i + 1]));
        $chapa = Chapa::factory()->for($eleicao)->homologada()->create(['numero' => 20, 'nome' => 'Chapa Vinte']);
        foreach ($cargos->reverse() as $cargo) {
            Candidato::factory()->for($chapa)->for($cargo)->create(['eleitor_id' => Eleitor::factory()->create(['nome' => "Aluno {$cargo->titulo}"])->id]);
        }
        Chapa::factory()->for($eleicao)->homologada()->create(['numero' => 10, 'nome' => 'Chapa Dez']);
        Chapa::factory()->for($eleicao)->create(['numero' => 30, 'nome' => 'Chapa Pendente']);
        Chapa::factory()->for($eleicao)->indeferida()->create(['numero' => 40, 'nome' => 'Chapa Indeferida']);
        Chapa::factory()->homologada()->create(['nome' => 'Chapa de Outra Eleição']);

        $this->get('/')->assertOk()
            ->assertSee($eleicao->titulo)
            ->assertSeeInOrder(['Chapa Dez', 'Chapa Vinte', 'Presidente', 'Aluno Presidente', 'Tesoureiro', 'Aluno Tesoureiro'])
            ->assertDontSee('Chapa Pendente')
            ->assertDontSee('Chapa Indeferida')
            ->assertDontSee('Chapa de Outra Eleição');
    }

    public function test_cada_fase_diz_o_que_fazer_agora(): void
    {
        $eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create();

        $this->get('/')->assertSee('Inscrições de chapas abertas')->assertSee('Inscrever uma chapa')
            ->assertSee('Até <strong>'.$eleicao->inscricao_fecha_em->format('d/m/Y \à\s H:i').'</strong>', false)
            ->assertDontSee('Como votar');

        $this->travel(15)->days();
        $this->get('/')->assertSee('Inscrições encerradas')->assertDontSee('Inscrever uma chapa')->assertSee('Como votar')
            ->assertSee('Nenhuma chapa homologada ainda');

        $this->travel(2)->days();
        $this->get('/')->assertSee('Votação aberta')->assertSee('documento com foto')->assertSee('aria-current="step"', false);

        $this->travel(5)->days();
        $this->get('/')->assertSee('Votação encerrada')->assertDontSee('Como votar');
    }

    public function test_antes_das_inscricoes_e_sem_datas(): void
    {
        $eleicao = Eleicao::factory()->ativa()->create(['inscricao_abre_em' => null, 'inscricao_fecha_em' => null, 'votacao_abre_em' => null, 'votacao_fecha_em' => null]);
        $this->get('/')->assertSee('Datas a definir')->assertDontSee('Calendário');

        $eleicao->update(Eleicao::factory()->raw());
        $this->get('/')->assertSee('As inscrições de chapas ainda não abriram');
    }

    public function test_nao_mostra_comparecimento_nem_resultado(): void
    {
        $eleicao = Eleicao::factory()->votacaoAberta()->ativa()->create();
        Chapa::factory()->for($eleicao)->homologada()->create();

        $this->get('/')->assertOk()->assertDontSee('Comparecimento')->assertDontSee('Participação')->assertDontSee('votos');
    }

    public function test_quem_entrou_volta_para_a_sua_area(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/')->assertSee('Ir para o painel')->assertSee(route('admin.painel'), false);

        $this->actingAs(User::factory()->create(['perfil' => 'mesario']));
        $this->get('/')->assertSee('Ir para a mesa')->assertSee(route('mesario'), false);
    }

    public function test_health_responde_json_com_banco_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['banco' => 'ok']);
    }

    public function test_status_do_terminal_nao_e_mais_publico_na_api(): void
    {
        $terminal = Terminal::factory()->create(['numero' => 1]);

        $this->getJson("/api/terminais/{$terminal->id}/status")->assertNotFound();
        $this->getJson('/urna/status')->assertForbidden();
    }

    public function test_pagina_inicial_aponta_a_ativacao_do_terminal(): void
    {
        $this->get('/')->assertOk()->assertSee('/terminal/ativar', false);
    }
}
