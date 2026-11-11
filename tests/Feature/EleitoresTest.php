<?php

namespace Tests\Feature;

use App\Models\Candidato;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** H4 · Admin cadastra eleitores um a um e por CSV. */
class EleitoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function csv(string $conteudo): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('eleitores.csv', $conteudo);
    }

    public function test_admin_cadastra_eleitor(): void
    {
        $this->post('/admin/eleitores', [
            'matricula' => ' 2024001 ', 'nome' => 'Ana   Lima', 'turma' => 'ADS 3', 'email' => '',
        ])->assertRedirect('/admin/eleitores');

        $this->assertDatabaseHas('eleitores', ['matricula' => '2024001', 'nome' => 'Ana Lima', 'turma' => 'ADS 3', 'email' => null]);
    }

    public function test_matricula_repetida_ou_invalida_e_nome_vazio_sao_rejeitados(): void
    {
        $existente = Eleitor::factory()->create();

        $this->post('/admin/eleitores', ['matricula' => $existente->matricula, 'nome' => 'Outro'])
            ->assertSessionHasErrors('matricula');
        $this->post('/admin/eleitores', ['matricula' => '2024 001', 'nome' => ''])
            ->assertSessionHasErrors(['matricula', 'nome']);
    }

    public function test_admin_edita_eleitor_mantendo_a_matricula(): void
    {
        $eleitor = Eleitor::factory()->create();

        $this->put("/admin/eleitores/{$eleitor->id}", ['matricula' => $eleitor->matricula, 'nome' => 'Nome Corrigido'])
            ->assertRedirect('/admin/eleitores');

        $this->assertSame('Nome Corrigido', $eleitor->refresh()->nome);
    }

    public function test_busca_por_matricula_ou_nome(): void
    {
        Eleitor::factory()->create(['matricula' => '2024555', 'nome' => 'Joana Prado']);
        Eleitor::factory()->create(['matricula' => '2023111', 'nome' => 'Marcos Teles']);

        $this->get('/admin/eleitores?busca=2024555')->assertOk()->assertSee('Joana Prado')->assertDontSee('Marcos Teles');
        $this->get('/admin/eleitores?busca=teles')->assertOk()->assertSee('Marcos Teles')->assertDontSee('Joana Prado');
    }

    public function test_importacao_com_3_linhas_validas_e_1_invalida(): void
    {
        $csv = "matricula;nome;turma;email\n"
            ."2024101;Ana Lima;ADS 3;ana@aluno.edu.br\n"
            ."2024102;Bruno Carvalho;;\n"
            ."2024103;Carla Souza;SI 2;nao-e-email\n"
            ."2024104;Diego Alves;SI 2;\n";

        $this->post('/admin/eleitores/importar', ['arquivo' => $this->csv($csv)])
            ->assertRedirect('/admin/eleitores/importar');

        $relatorio = session('importacao');
        $this->assertSame(3, $relatorio['criados']);
        $this->assertSame(0, $relatorio['atualizados']);
        $this->assertSame(1, $relatorio['total_falhas']);
        $this->assertSame(4, $relatorio['falhas'][0]['linha']);
        $this->assertSame('2024103', $relatorio['falhas'][0]['matricula']);
        $this->assertStringContainsString('e-mail', $relatorio['falhas'][0]['motivo']);

        $this->assertSame(3, Eleitor::count());
        $this->assertDatabaseMissing('eleitores', ['matricula' => '2024103']);

        $this->get('/admin/eleitores/importar')->assertOk()->assertSee('Linhas com erro')->assertSee('2024103');
    }

    public function test_matricula_existente_no_csv_atualiza_o_nome_sem_duplicar(): void
    {
        Eleitor::factory()->create(['matricula' => '2024101', 'nome' => 'Nome Antigo', 'turma' => 'ADS 1', 'email' => 'antigo@aluno.edu.br']);

        $this->post('/admin/eleitores/importar', ['arquivo' => $this->csv("matricula;nome;turma;email\n2024101;Nome Novo;;\n")]);

        $this->assertSame(1, session('importacao')['atualizados']);
        $this->assertSame(1, Eleitor::count());
        $this->assertDatabaseHas('eleitores', [
            'matricula' => '2024101', 'nome' => 'Nome Novo', 'turma' => 'ADS 1', 'email' => 'antigo@aluno.edu.br',
        ]);
    }

    public function test_csv_do_excel_com_bom_virgula_e_cabecalho_acentuado(): void
    {
        $utf8 = "\xEF\xBB\xBFMatrícula,Nome,E-mail\n2024201,João Araújo,joao@aluno.edu.br\n";
        $this->post('/admin/eleitores/importar', ['arquivo' => $this->csv($utf8)]);
        $this->assertDatabaseHas('eleitores', ['matricula' => '2024201', 'nome' => 'João Araújo', 'email' => 'joao@aluno.edu.br']);

        $windows1252 = mb_convert_encoding("matricula;nome\n2024202;Conceição Brandão\n", 'Windows-1252', 'UTF-8');
        $this->post('/admin/eleitores/importar', ['arquivo' => $this->csv($windows1252)]);
        $this->assertDatabaseHas('eleitores', ['matricula' => '2024202', 'nome' => 'Conceição Brandão']);
    }

    public function test_csv_sem_cabecalho_e_recusado_sem_gravar_nada(): void
    {
        $this->post('/admin/eleitores/importar', ['arquivo' => $this->csv("2024101;Ana Lima\n")])
            ->assertSessionHasErrors('arquivo');

        $this->assertSame(0, Eleitor::count());
    }

    public function test_eleitor_sem_vinculos_pode_ser_excluido(): void
    {
        $eleitor = Eleitor::factory()->create();

        $this->delete("/admin/eleitores/{$eleitor->id}")->assertRedirect('/admin/eleitores');

        $this->assertModelMissing($eleitor);
    }

    public function test_eleitor_liberado_ou_candidato_nao_pode_ser_excluido(): void
    {
        $liberado = SessaoVotacao::factory()->create()->eleitor;
        $candidato = Candidato::factory()->create()->eleitor;

        $this->delete("/admin/eleitores/{$liberado->id}")->assertSessionHasErrors(['geral' => "{$liberado->nome} já foi liberado para votar e faz parte da ata: não pode ser excluído."]);
        $this->delete("/admin/eleitores/{$candidato->id}")->assertSessionHasErrors('geral');

        $this->assertModelExists($liberado);
        $this->assertModelExists($candidato);
    }

    public function test_mesario_nao_acessa_eleitores(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/eleitores')->assertForbidden();
    }

    public function test_modelo_de_csv_para_baixar(): void
    {
        $this->get('/admin/eleitores/modelo.csv')->assertOk()->assertDownload('eleitores-modelo.csv');
    }
}
