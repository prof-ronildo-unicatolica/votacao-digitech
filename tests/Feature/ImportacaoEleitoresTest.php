<?php

namespace Tests\Feature;

use App\Models\Eleitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * H4 · Importar eleitores por CSV no fluxo da D19: enviar → conferir → cancelar, ignorar erros
 * ou confirmar. Arquivos em tests/Fixtures/eleitores: limpo, alterado e mal formatados.
 */
class ImportacaoEleitoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create());
    }

    private function fixture(string $nome): string
    {
        return file_get_contents(base_path("tests/Fixtures/eleitores/{$nome}"));
    }

    private function enviar(string $conteudo): TestResponse
    {
        return $this->post('/admin/eleitores/importar', ['arquivo' => UploadedFile::fake()->createWithContent('eleitores.csv', $conteudo)]);
    }

    private function conferir(string $conteudo): string
    {
        $this->enviar($conteudo)->assertRedirect('/admin/eleitores/importar/conferir');
        preg_match('/name="assinatura" value="([0-9a-f]+)"/', $this->get('/admin/eleitores/importar/conferir')->assertOk()->getContent(), $m);

        return $m[1];
    }

    private function confirmar(string $assinatura, ?string $acao): TestResponse
    {
        return $this->post('/admin/eleitores/importar/confirmar', array_filter(['assinatura' => $assinatura, 'acao' => $acao]));
    }

    // CSV limpo -------------------------------------------------------------------------------

    public function test_csv_limpo_so_grava_depois_de_confirmar(): void
    {
        $assinatura = $this->conferir($this->fixture('limpo.csv'));

        $this->assertSame(0, Eleitor::count(), 'Enviar e conferir não gravam nada.');
        $this->get('/admin/eleitores/importar/conferir')
            ->assertSee('Nada foi gravado ainda')
            ->assertSee('3 alunos novos')
            ->assertSee('Confirmar importação de 3 linhas');

        $this->confirmar($assinatura, 'importar')->assertRedirect('/admin/eleitores/importar');

        $this->assertSame(3, Eleitor::count());
        $this->assertDatabaseHas('eleitores', ['matricula' => '2024101', 'nome' => 'Ana Beatriz Lima', 'turma' => 'ADS 3', 'email' => 'ana.lima@aluno.edu.br']);
        $this->assertDatabaseHas('eleitores', ['matricula' => '2024102', 'turma' => 'ADS 3', 'email' => null]);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->get('/admin/eleitores/importar')->assertSee('Resultado da importação')->assertSee('Todas as linhas do arquivo foram importadas.');
    }

    public function test_csv_limpo_com_bom_ou_salvo_em_windows_1252(): void
    {
        $this->confirmar($this->conferir("\xEF\xBB\xBF".$this->fixture('limpo.csv')), 'importar');
        $this->assertSame(3, Eleitor::count());

        $this->confirmar($this->conferir(mb_convert_encoding("matricula;nome\n2024202;Conceição Brandão\n", 'Windows-1252', 'UTF-8')), 'importar');
        $this->assertDatabaseHas('eleitores', ['matricula' => '2024202', 'nome' => 'Conceição Brandão']);
    }

    public function test_o_modelo_para_baixar_importa_sem_erro(): void
    {
        $this->get('/admin/eleitores/modelo.csv')->assertOk()->assertDownload('eleitores-modelo.csv');

        $this->confirmar($this->conferir(file_get_contents(base_path('docs/exemplos/eleitores.csv'))), 'importar');

        $this->assertGreaterThan(0, Eleitor::count());
    }

    // CSV alterado: cabeçalho com apelidos, vírgula, CRLF, CAIXA ALTA, espaços -----------------

    public function test_csv_alterado_e_atualizacao_de_quem_ja_esta_cadastrado(): void
    {
        Eleitor::factory()->create(['matricula' => '2024101', 'nome' => 'Ana Lima', 'turma' => 'ADS 1', 'email' => null]);

        $assinatura = $this->conferir($this->fixture('alterado.csv'));

        $this->get('/admin/eleitores/importar/conferir')
            ->assertSee('Colunas ignoradas (não fazem parte do cadastro): “Observação”.', false)
            ->assertSee('Cadastros que vão mudar')
            ->assertSeeInOrder(['Ana Lima', 'Ana Beatriz da Silva Luz', 'ADS 1', 'ADS 3'])
            ->assertSee('“ANA BEATRIZ DA SILVA LUZ” → “Ana Beatriz da Silva Luz”', false)
            ->assertSee('“bruno carvalho dos santos” → “Bruno Carvalho dos Santos”', false)
            ->assertSee('2 linhas em branco foram ignoradas');

        $this->confirmar($assinatura, 'importar')->assertRedirect();

        $this->assertSame(3, Eleitor::count(), 'A matrícula 2024101 ("2024 101" no arquivo) atualizou, não duplicou.');
        $this->assertDatabaseHas('eleitores', ['matricula' => '2024101', 'nome' => 'Ana Beatriz da Silva Luz', 'turma' => 'ADS 3', 'email' => 'ana.lima@aluno.edu.br']);
        $this->assertDatabaseHas('eleitores', ['matricula' => '2024102', 'nome' => 'Bruno Carvalho dos Santos']);
        $this->assertDatabaseHas('eleitores', ['matricula' => '2024104', 'nome' => 'Souza, Carla', 'turma' => 'SI 2']);
    }

    public function test_celula_vazia_nao_apaga_e_cadastro_igual_nao_muda(): void
    {
        Eleitor::factory()->create(['matricula' => '2024101', 'nome' => 'Ana Lima', 'turma' => 'ADS 1', 'email' => 'antigo@aluno.edu.br']);

        $assinatura = $this->conferir("matricula;nome;turma;email\n2024101;ANA LIMA;;\n");
        $this->get('/admin/eleitores/importar/conferir')->assertSee('Sem alteração')->assertDontSee('Cadastros que vão mudar');

        $this->confirmar($assinatura, 'importar');

        $this->assertDatabaseHas('eleitores', ['matricula' => '2024101', 'nome' => 'Ana Lima', 'turma' => 'ADS 1', 'email' => 'antigo@aluno.edu.br']);
        $this->assertSame(1, session('importacao_eleitores_resultado')['iguais']);
    }

    // CSV com erros: conferir, cancelar, ignorar, confirmar ----------------------------------

    public function test_conferencia_aponta_linha_e_coluna_de_cada_erro(): void
    {
        $this->conferir($this->fixture('com-erros.csv'));

        $this->get('/admin/eleitores/importar/conferir')
            ->assertSee('7 linhas com erro. Escolha:')
            ->assertSee('Linha 4, e-mail:</strong>', false)
            ->assertSee('Linha 5, matrícula:</strong>', false)
            ->assertSee('Linha 6, matrícula:</strong> A matrícula só pode ter letras, números, ponto e hífen.', false)
            ->assertSee('virou notação científica no Excel')
            ->assertSee('Linha 8, nome:</strong>', false)
            ->assertSee('A matrícula 2024108 se repete neste arquivo (linha 10).')
            ->assertSee('A matrícula 2024108 se repete neste arquivo (linha 9).');
    }

    public function test_cancelar_e_rollback(): void
    {
        $assinatura = $this->conferir($this->fixture('com-erros.csv'));

        $this->confirmar($assinatura, 'cancelar')
            ->assertRedirect('/admin/eleitores/importar')
            ->assertSessionHas('status', 'Importação cancelada. O arquivo foi descartado e nenhum aluno foi gravado.');

        $this->assertSame(0, Eleitor::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_com_erros_e_preciso_escolher_e_importar_tudo_e_recusado(): void
    {
        $assinatura = $this->conferir($this->fixture('com-erros.csv'));

        $this->confirmar($assinatura, null)->assertSessionHasErrors(['acao' => 'Escolha o que fazer com as linhas com erro.']);
        $this->confirmar($assinatura, 'importar')->assertSessionHasErrors('acao');
        $this->assertSame(0, Eleitor::count());
    }

    public function test_ignorar_os_erros_importa_so_as_linhas_prontas(): void
    {
        $assinatura = $this->conferir($this->fixture('com-erros.csv'));

        $this->confirmar($assinatura, 'ignorar')->assertRedirect('/admin/eleitores/importar');

        $this->assertSame(['2024101', '2024102'], Eleitor::orderBy('matricula')->pluck('matricula')->all());
        $this->get('/admin/eleitores/importar')->assertSee('Linhas ignoradas')->assertSee('Linha 4, e-mail:');
    }

    public function test_se_o_cadastro_mudou_depois_da_conferencia_tudo_e_desfeito(): void
    {
        $assinatura = $this->conferir($this->fixture('limpo.csv'));

        // Enquanto o admin conferia, alguém cadastrou um dos alunos pela tela.
        Eleitor::factory()->create(['matricula' => '2024103', 'nome' => 'Carla S.']);

        $this->confirmar($assinatura, 'importar')
            ->assertRedirect('/admin/eleitores/importar/conferir')
            ->assertSessionHas('aviso');

        $this->assertSame(['2024103'], Eleitor::pluck('matricula')->all(), 'Nenhuma linha do arquivo entrou.');
        $this->get('/admin/eleitores/importar/conferir')->assertSee('Cadastros que vão mudar')->assertSee('Carla S.');
    }

    // CSV mal formatado ----------------------------------------------------------------------

    public function test_arquivos_que_nao_servem(): void
    {
        $casos = [
            'sem cabeçalho' => [$this->fixture('sem-cabecalho.csv'), 'O cabeçalho precisa ter as colunas matricula e nome'],
            'só o cabeçalho' => [$this->fixture('so-cabecalho.csv'), 'O arquivo só tem o cabeçalho: nenhum aluno para importar.'],
            'vazio' => ['', 'O arquivo está vazio.'],
            'planilha .xlsx renomeada' => ["PK\x03\x04\x14\x00".str_repeat("\0", 20), 'O arquivo não é um CSV de texto'],
            'separador tab' => ["matricula\tnome\n2024101\tAna\n", 'O cabeçalho precisa ter as colunas matricula e nome'],
        ];

        foreach ($casos as $caso => [$conteudo, $mensagem]) {
            $this->enviar($conteudo)->assertSessionHasErrors('arquivo');
            $this->assertStringContainsString($mensagem, implode(' ', session('errors')->get('arquivo')), $caso);
        }

        $this->assertSame(0, Eleitor::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_linha_com_mais_colunas_que_o_cabecalho(): void
    {
        $this->conferir($this->fixture('colunas-a-mais.csv'));

        $this->get('/admin/eleitores/importar/conferir')->assertSee('A linha tem mais colunas que o cabeçalho')->assertSee('1 linha com erro');
    }

    public function test_arquivo_que_nao_e_csv_e_recusado_pela_extensao(): void
    {
        $this->post('/admin/eleitores/importar', ['arquivo' => UploadedFile::fake()->create('eleitores.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors(['arquivo' => 'Envie um arquivo .csv (no Excel: Salvar como → CSV UTF-8).']);
    }

    public function test_mesario_nao_acessa_a_importacao(): void
    {
        $this->actingAs(User::factory()->create(['perfil' => 'mesario']));

        $this->get('/admin/eleitores/importar')->assertForbidden();
        $this->enviar($this->fixture('limpo.csv'))->assertForbidden();
    }
}
