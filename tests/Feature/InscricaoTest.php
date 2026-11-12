<?php

namespace Tests\Feature;

use App\Enums\OrigemChapa;
use App\Enums\SituacaoChapa;
use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/** H14 · Representante inscreve a chapa pelo formulário público, sem login. */
class InscricaoTest extends TestCase
{
    use RefreshDatabase;

    private Eleicao $eleicao;

    /** @var Collection<int, Cargo> */
    private Collection $cargos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create(['titulo' => 'Eleição 2026.2']);
        $this->cargos = collect(['Presidente', 'Vice-presidente', 'Tesoureiro'])->map(
            fn (string $titulo, int $i) => Cargo::factory()->for($this->eleicao)->create(['titulo' => $titulo, 'ordem' => $i + 1]),
        );

        foreach (range(1, 8) as $n) {
            Eleitor::factory()->create(['matricula' => "202410{$n}", 'nome' => "Aluno {$n}"]);
        }
    }

    /** Dados de uma inscrição válida; $matriculas na ordem dos cargos. */
    private function dados(array $matriculas = ['2024101', '2024102', '2024103'], array $extra = []): array
    {
        return $extra + [
            'nome' => 'Movimento Estudantil',
            'contato_email' => 'responsavel@aluno.edu.br',
            'contato_telefone' => '(88) 99999-0000',
            'candidatos' => $this->cargos->pluck('id')->combine($matriculas)->all(),
        ];
    }

    private function campo(int $indiceDoCargo): string
    {
        return 'candidatos.'.$this->cargos[$indiceDoCargo]->id;
    }

    public function test_formulario_tem_um_campo_de_matricula_por_cargo_na_ordem(): void
    {
        $this->get('/inscricao')->assertOk()
            ->assertSeeInOrder(['1. Presidente', '2. Vice-presidente', '3. Tesoureiro'])
            ->assertSee('name="_token"', false);
    }

    public function test_inscricao_valida_entra_pendente_com_comprovante(): void
    {
        $this->post('/inscricao', $this->dados())->assertRedirect('/inscricao/comprovante');

        $chapa = Chapa::sole();
        $this->assertSame(1, $chapa->numero);
        $this->assertSame('Movimento Estudantil', $chapa->nome);
        $this->assertSame(SituacaoChapa::Pendente, $chapa->situacao);
        $this->assertSame(OrigemChapa::Formulario, $chapa->origem);
        $this->assertSame(['2024101', '2024102', '2024103'], $chapa->composicao()->get()->pluck('eleitor.matricula')->all());

        $this->get('/inscricao/comprovante')->assertOk()
            ->assertSee('Inscrição recebida')->assertSee('Pendente')
            ->assertSeeInOrder(['Presidente', 'Aluno 1', 'Vice-presidente', 'Aluno 2', 'Tesoureiro', 'Aluno 3'])
            ->assertDontSee('responsavel@aluno.edu.br');
    }

    public function test_comprovante_so_aparece_para_quem_inscreveu(): void
    {
        $this->get('/inscricao/comprovante')->assertRedirect('/inscricao');
    }

    public function test_antes_da_abertura_e_depois_do_fim_a_inscricao_e_rejeitada(): void
    {
        $this->travelTo($this->eleicao->inscricao_abre_em->copy()->subMinute());
        $this->get('/inscricao')->assertOk()->assertSee('As inscrições ainda não abriram')->assertDontSee('Enviar inscrição');
        $this->post('/inscricao', $this->dados())->assertSessionHasErrors('geral');

        // Exatamente no horário de fim já está fechado (o período exclui o fim).
        $this->travelTo($this->eleicao->inscricao_fecha_em);
        $this->get('/inscricao')->assertOk()->assertSee('As inscrições foram encerradas');
        $this->post('/inscricao', $this->dados())->assertSessionHasErrors('geral');

        $this->assertSame(0, Chapa::count());
    }

    public function test_envio_e_conferido_no_servidor_mesmo_com_a_pagina_aberta_antes_do_fim(): void
    {
        $this->get('/inscricao')->assertSee('Enviar inscrição');

        $this->travelTo($this->eleicao->inscricao_fecha_em->copy()->addSecond());
        $this->post('/inscricao', $this->dados())->assertSessionHasErrors('geral');
        $this->assertSame(0, Chapa::count());
    }

    public function test_cargo_sem_candidato_e_rejeitado_e_nada_e_gravado(): void
    {
        $this->post('/inscricao', $this->dados(['2024101', '', '2024103']))
            ->assertSessionHasErrors([$this->campo(1) => 'Informe a matrícula do candidato a Vice-presidente.']);

        $this->assertSame(0, Chapa::count());
        $this->assertSame(0, Candidato::count());
    }

    public function test_matricula_inexistente_e_rejeitada(): void
    {
        $this->post('/inscricao', $this->dados(['2024101', '2099999', '2024103']))
            ->assertSessionHasErrors($this->campo(1));

        $this->assertSame(0, Chapa::count());
    }

    public function test_mesmo_aluno_em_dois_cargos_e_rejeitado(): void
    {
        $this->post('/inscricao', $this->dados(['2024101', '2024102', '2024101']))
            ->assertSessionHasErrors([$this->campo(2) => 'Aluno 1 já está no cargo Presidente desta chapa: cada aluno ocupa um cargo só.'])
            ->assertSessionDoesntHaveErrors($this->campo(0));
    }

    public function test_aluno_que_ja_esta_em_outra_chapa_e_rejeitado(): void
    {
        $this->post('/inscricao', $this->dados());

        $this->post('/inscricao', $this->dados(['2024104', '2024102', '2024105'], ['nome' => 'Outra Chapa']))
            ->assertSessionHasErrors([$this->campo(1) => 'Aluno 2 já está em outra chapa desta eleição.']);

        $this->assertSame(1, Chapa::count());
    }

    public function test_nome_de_chapa_repetido_na_eleicao_e_rejeitado(): void
    {
        $this->post('/inscricao', $this->dados());

        $this->post('/inscricao', $this->dados(['2024104', '2024105', '2024106'], ['nome' => 'movimento   estudantil']))
            ->assertSessionHasErrors('nome');
    }

    public function test_duas_inscricoes_seguidas_recebem_numeros_diferentes(): void
    {
        Chapa::factory()->for($this->eleicao)->create(['numero' => 7]);

        $this->post('/inscricao', $this->dados());
        $this->post('/inscricao', $this->dados(['2024104', '2024105', '2024106'], ['nome' => 'Renova']));

        $this->assertSame([7, 8, 9], $this->eleicao->chapas()->pluck('numero')->all());
    }

    public function test_contato_obrigatorio_e_telefone_valido(): void
    {
        $this->post('/inscricao', $this->dados(extra: ['contato_email' => 'nao-e-email', 'contato_telefone' => 'abc']))
            ->assertSessionHasErrors(['contato_email', 'contato_telefone']);
    }

    public function test_sem_cargos_definidos_nao_ha_formulario(): void
    {
        $this->eleicao->cargos()->delete();

        $this->get('/inscricao')->assertOk()->assertSee('A comissão ainda está definindo os cargos')->assertDontSee('Enviar inscrição');
    }

    public function test_mais_de_5_envios_por_minuto_do_mesmo_ip_sao_barrados(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/inscricao', $this->dados(['', '', '']))->assertSessionDoesntHaveErrors('geral');
        }

        $this->post('/inscricao', $this->dados())->assertSessionHasErrors('geral');
        $this->assertSame(0, Chapa::count());
    }

    public function test_conferencia_do_nome_devolve_so_o_nome(): void
    {
        $this->getJson('/inscricao/aluno?matricula=2024101')->assertOk()->assertExactJson(['encontrado' => true, 'nome' => 'Aluno 1']);
        $this->getJson('/inscricao/aluno?matricula=2099999')->assertOk()->assertExactJson(['encontrado' => false]);

        $this->travelTo($this->eleicao->inscricao_fecha_em);
        $this->getJson('/inscricao/aluno?matricula=2024101')->assertNotFound();
    }

    public function test_pagina_publica_nao_lista_outras_chapas(): void
    {
        $this->post('/inscricao', $this->dados());
        $this->flushSession();

        $this->get('/inscricao')->assertOk()->assertDontSee('Movimento Estudantil')->assertDontSee('Aluno 1');
    }

    public function test_mesario_ou_admin_com_matricula_nao_pode_ser_candidato(): void
    {
        User::factory()->create(['perfil' => 'mesario', 'matricula' => '2024102']);
        User::factory()->admin()->create(['matricula' => '2024103']);

        $this->post('/inscricao', $this->dados())->assertSessionHasErrors([
            $this->campo(1) => 'Aluno 2 é mesário e não pode ser candidato: quem libera votos não concorre.',
            $this->campo(2) => 'Aluno 3 faz parte da comissão eleitoral (admin) e não pode ser candidato.',
        ]);
        $this->assertSame(0, Chapa::count());
    }
}
