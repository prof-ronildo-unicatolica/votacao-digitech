<?php

namespace Tests\Feature;

use App\Enums\OrigemChapa;
use App\Enums\SituacaoChapa;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\User;
use App\Support\InscricaoDeChapa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * H15 · Importar chapas por CSV: enviar → conferir → cancelar, ignorar erros ou confirmar (D19).
 *
 * Os arquivos ficam em tests/Fixtures/chapas: um limpo, um "alterado" (como sai de planilhas
 * reais: vírgula, CRLF, maiúsculas, espaços, colunas em outra ordem) e vários mal formatados.
 */
class ImportacaoChapasTest extends TestCase
{
    use RefreshDatabase;

    private Eleicao $eleicao;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->actingAs(User::factory()->admin()->create());
        $this->eleicao = Eleicao::factory()->inscricoesAbertas()->ativa()->create();

        foreach (range(101, 112) as $n) {
            Eleitor::factory()->create(['matricula' => "2024{$n}", 'nome' => "Aluno {$n}"]);
        }
    }

    private function comCargos(): void
    {
        foreach (['Presidente', 'Vice-presidente', 'Tesoureiro'] as $i => $titulo) {
            Cargo::factory()->for($this->eleicao)->create(['titulo' => $titulo, 'ordem' => $i + 1]);
        }
    }

    private function fixture(string $nome): string
    {
        return file_get_contents(base_path("tests/Fixtures/chapas/{$nome}"));
    }

    private function enviar(string $conteudo, string $nome = 'chapas.csv'): TestResponse
    {
        return $this->post('/admin/chapas/importar', ['arquivo' => UploadedFile::fake()->createWithContent($nome, $conteudo)]);
    }

    /** Envia, abre a conferência e devolve a assinatura que o formulário manda de volta. */
    private function conferir(string $conteudo): string
    {
        $this->enviar($conteudo)->assertRedirect('/admin/chapas/importar/conferir');
        $pagina = $this->get('/admin/chapas/importar/conferir')->assertOk()->getContent();
        preg_match('/name="assinatura" value="([0-9a-f]+)"/', $pagina, $m);

        return $m[1];
    }

    private function confirmar(string $assinatura, ?string $acao): TestResponse
    {
        return $this->post('/admin/chapas/importar/confirmar', array_filter(['assinatura' => $assinatura, 'acao' => $acao]));
    }

    private function composicao(int $numero): array
    {
        return Chapa::where('numero', $numero)->sole()->composicao()->get()
            ->mapWithKeys(fn ($c) => [$c->cargo->titulo => $c->eleitor->matricula])->all();
    }

    // CSV limpo -------------------------------------------------------------------------------

    public function test_csv_limpo_so_grava_depois_de_confirmar(): void
    {
        $this->comCargos();
        $assinatura = $this->conferir($this->fixture('limpo.csv'));

        $this->assertSame(0, Chapa::count(), 'Enviar e conferir não gravam nada.');
        $this->get('/admin/chapas/importar/conferir')
            ->assertSee('Nada foi gravado ainda')
            ->assertSeeInOrder(['Movimento Estudantil', 'Aluno 101', 'Aluno 102', 'Aluno 103', 'Pronta'])
            ->assertSee('Confirmar importação de 2 chapas');

        $this->confirmar($assinatura, 'importar')->assertRedirect('/admin/chapas/importar');

        $this->assertSame(2, Chapa::count());
        $chapa = Chapa::where('numero', 1)->sole();
        $this->assertSame('Movimento Estudantil', $chapa->nome);
        $this->assertSame(SituacaoChapa::Pendente, $chapa->situacao);
        $this->assertSame(OrigemChapa::Csv, $chapa->origem);
        $this->assertSame('movimento@aluno.edu.br', $chapa->contato_email);
        $this->assertSame('(88) 99999-0001', $chapa->contato_telefone);
        $this->assertSame(['Presidente' => '2024101', 'Vice-presidente' => '2024102', 'Tesoureiro' => '2024103'], $this->composicao(1));
        $this->assertSame([], Storage::disk('local')->allFiles(), 'O arquivo em conferência é apagado.');

        $this->get('/admin/chapas/importar')->assertSee('Resultado da importação')->assertSee('Inovação Acadêmica');
    }

    public function test_csv_limpo_com_bom_ou_salvo_em_windows_1252(): void
    {
        $this->comCargos();
        $limpo = $this->fixture('limpo.csv');

        $this->confirmar($this->conferir("\xEF\xBB\xBF".$limpo), 'importar');
        $this->assertSame(2, Chapa::count());

        Chapa::query()->delete();
        $this->confirmar($this->conferir(mb_convert_encoding($limpo, 'Windows-1252', 'UTF-8')), 'importar');
        $this->assertSame('Inovação Acadêmica', Chapa::where('numero', 2)->value('nome'));
    }

    public function test_o_modelo_para_baixar_importa_sem_erro(): void
    {
        $this->get('/admin/chapas/modelo.csv')->assertOk()->assertDownload('chapas-modelo.csv');

        // Eleição sem cargos: o cabeçalho do modelo cria os 5 cargos.
        $this->confirmar($this->conferir(file_get_contents(base_path('docs/exemplos/chapas.csv'))), 'importar');

        $this->assertSame(['Presidente', 'Vice-presidente', '1º Secretário', '2º Secretário', 'Tesoureiro'], $this->eleicao->cargos()->pluck('titulo')->all());
        $this->assertSame(2, Chapa::count());
    }

    // Cabeçalho cria os cargos ---------------------------------------------------------------

    public function test_cabecalho_cria_os_cargos_na_ordem_das_colunas_com_nomes_padronizados(): void
    {
        $csv = "numero_chapa;nome_chapa;PRESIDENTE; vice-presidente ;1o secretário;TESOUREIRO\n1;Chapa Um;2024101;2024102;2024103;2024104\n";

        $assinatura = $this->conferir($csv);
        $this->get('/admin/chapas/importar/conferir')
            ->assertSee('O cabeçalho vai criar os cargos')
            ->assertSee('Cargo “PRESIDENTE” → “Presidente”', false)
            ->assertSee('Cargo “1o secretário” → “1º Secretário”', false);
        $this->assertSame(0, Cargo::count(), 'Os cargos só são criados ao confirmar.');

        $this->confirmar($assinatura, 'importar');

        $this->assertSame(
            [1 => 'Presidente', 2 => 'Vice-presidente', 3 => '1º Secretário', 4 => 'Tesoureiro'],
            $this->eleicao->cargos()->pluck('titulo', 'ordem')->all(),
        );
        $this->assertSame('2024103', $this->composicao(1)['1º Secretário']);
    }

    public function test_cargos_iguais_escritos_de_jeitos_diferentes_sao_rejeitados(): void
    {
        $this->enviar("numero_chapa;nome_chapa;Vice-presidente;VICE PRESIDENTE\n1;Chapa;2024101;2024102\n")
            ->assertSessionHasErrors(['arquivo' => 'As colunas “Vice-presidente” e “VICE PRESIDENTE” são o mesmo cargo.']);
    }

    public function test_com_cargos_definidos_as_colunas_sao_conferidas_com_eles(): void
    {
        $this->comCargos();

        $this->enviar($this->fixture('cargo-que-nao-existe.csv'))->assertSessionHasErrors('arquivo');
        $erros = session('errors')->get('arquivo');

        $this->assertContains('Falta a coluna do cargo Tesoureiro.', $erros);
        $this->assertContains('A coluna “Diretor de Esportes” não é um cargo desta eleição. Cargos: Presidente, Vice-presidente e Tesoureiro.', $erros);
        $this->assertSame(0, Cargo::where('titulo', 'Diretor de Esportes')->count());
    }

    // CSV alterado (como sai de planilhas reais) --------------------------------------------

    public function test_csv_alterado_virgula_crlf_maiusculas_espacos_e_colunas_fora_de_ordem(): void
    {
        $this->comCargos();
        $assinatura = $this->conferir($this->fixture('alterado.csv'));

        $this->get('/admin/chapas/importar/conferir')
            ->assertSee('Nomes padronizados')
            ->assertSee('Coluna “VICE PRESIDENTE” → cargo Vice-presidente', false)
            ->assertSee('chapa “MOVIMENTO ESTUDANTIL” → “Movimento Estudantil”', false)
            ->assertSee('2 linhas em branco foram ignoradas');

        $this->confirmar($assinatura, 'importar')->assertRedirect();

        $this->assertSame(['Movimento Estudantil', 'Inovação Acadêmica, Já'], Chapa::orderBy('numero')->pluck('nome')->all());
        $this->assertSame(['Presidente' => '2024101', 'Vice-presidente' => '2024102', 'Tesoureiro' => '2024103'], $this->composicao(1));
        $this->assertSame('movimento@aluno.edu.br', Chapa::where('numero', 1)->value('contato_email'));
    }

    // CSV com erros: conferir, cancelar, ignorar, confirmar ----------------------------------

    public function test_conferencia_aponta_linha_e_coluna_de_cada_erro(): void
    {
        $this->comCargos();
        $this->conferir($this->fixture('com-erros.csv'));

        $this->get('/admin/chapas/importar/conferir')
            ->assertSee('3 chapas têm erro')
            ->assertSee('Linha 4, Tesoureiro:</strong>', false)->assertSee('Informe a matrícula do candidato a Tesoureiro.')
            ->assertSee('Linha 5, Presidente:</strong>', false)->assertSee('Matrícula 2024999 não cadastrada no eleitorado.')
            ->assertSee('virou notação científica no Excel')
            ->assertSee('Linha 6, numero_chapa:</strong>', false)->assertSee('Número da chapa “abc” inválido', false)
            ->assertSee('Aluno 110 já está no cargo Vice-presidente desta chapa')
            ->assertSee('Cancelar tudo (rollback)')
            ->assertSee('Ignorar os erros');
    }

    public function test_cancelar_e_rollback_nada_e_gravado_e_o_arquivo_e_descartado(): void
    {
        $this->comCargos();
        $assinatura = $this->conferir($this->fixture('com-erros.csv'));

        $this->confirmar($assinatura, 'cancelar')
            ->assertRedirect('/admin/chapas/importar')
            ->assertSessionHas('status', 'Importação cancelada. O arquivo foi descartado e nenhuma chapa foi gravada.');

        $this->assertSame(0, Chapa::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->get('/admin/chapas/importar/conferir')->assertRedirect('/admin/chapas/importar');
    }

    public function test_com_erros_e_preciso_escolher_e_importar_tudo_e_recusado(): void
    {
        $this->comCargos();
        $assinatura = $this->conferir($this->fixture('com-erros.csv'));

        $this->confirmar($assinatura, null)->assertSessionHasErrors(['acao' => 'Escolha o que fazer com as chapas com erro.']);
        $this->confirmar($assinatura, 'importar')->assertSessionHasErrors('acao');

        $this->assertSame(0, Chapa::count());
    }

    public function test_ignorar_os_erros_importa_so_as_chapas_prontas(): void
    {
        $this->comCargos();
        $assinatura = $this->conferir($this->fixture('com-erros.csv'));

        $this->confirmar($assinatura, 'ignorar')->assertRedirect('/admin/chapas/importar');

        $this->assertSame([1, 2], Chapa::orderBy('numero')->pluck('numero')->all());
        $this->get('/admin/chapas/importar')
            ->assertSee('Chapas ignoradas')
            ->assertSee('Linha 5, Presidente: Matrícula 2024999 não cadastrada no eleitorado.');
    }

    public function test_se_algo_mudou_depois_da_conferencia_tudo_e_desfeito(): void
    {
        $this->comCargos();
        $assinatura = $this->conferir($this->fixture('limpo.csv'));

        // Enquanto o admin conferia, um aluno do arquivo se inscreveu pelo formulário (H14).
        $cargos = $this->eleicao->cargos()->pluck('id');
        app(InscricaoDeChapa::class)->inscrever($this->eleicao, 'Chapa do Formulário', $cargos->combine(['2024104', '2024107', '2024108'])->all(), 'a@b.br', null, OrigemChapa::Formulario);

        $this->confirmar($assinatura, 'importar')
            ->assertRedirect('/admin/chapas/importar/conferir')
            ->assertSessionHas('aviso');

        $this->assertSame(['Chapa do Formulário'], Chapa::pluck('nome')->all(), 'Nenhuma chapa do arquivo entrou, nem a que continuava válida.');
        $this->get('/admin/chapas/importar/conferir')->assertSee('Aluno 104 já está em outra chapa desta eleição.');
    }

    // Repetições e regras da H14 -------------------------------------------------------------

    public function test_repetidas_no_arquivo_marcam_as_duas_chapas(): void
    {
        $this->comCargos();
        $this->conferir($this->fixture('repetidas.csv'));

        $this->get('/admin/chapas/importar/conferir')
            ->assertSee('Aluno 101 também está em outra chapa deste arquivo (linha 3).')
            ->assertSee('Aluno 101 também está em outra chapa deste arquivo (linha 2).')
            ->assertSee('O número 2 se repete neste arquivo (linha 4).')
            ->assertSee('Outra chapa deste arquivo tem o mesmo nome (linha 2).')
            ->assertSee('Nenhuma chapa está pronta')
            ->assertDontSee('Confirmar importação');
    }

    public function test_numero_ou_aluno_que_ja_estao_na_eleicao(): void
    {
        $this->comCargos();
        Chapa::factory()->for($this->eleicao)->create(['numero' => 1, 'nome' => 'Já Inscrita']);

        $this->conferir($this->fixture('limpo.csv'));

        $this->get('/admin/chapas/importar/conferir')->assertSee('Já existe a chapa número 1 nesta eleição.');
    }

    public function test_mesario_ou_admin_nao_pode_ser_candidato(): void
    {
        $this->comCargos();
        User::factory()->create(['perfil' => 'mesario', 'matricula' => '2024104']);
        User::factory()->admin()->create(['matricula' => '2024106']);

        $this->conferir($this->fixture('limpo.csv'));

        $this->get('/admin/chapas/importar/conferir')
            ->assertSee('Aluno 104 é mesário e não pode ser candidato')
            ->assertSee('Aluno 106 faz parte da comissão eleitoral (admin)');
    }

    // CSV mal formatado ----------------------------------------------------------------------

    public function test_cabecalho_sem_as_colunas_da_chapa(): void
    {
        $this->enviar($this->fixture('cabecalho-errado.csv'))->assertSessionHasErrors('arquivo');
        $erros = session('errors')->get('arquivo');

        $this->assertContains('Falta a coluna numero_chapa no cabeçalho.', $erros);
        $this->assertContains('Falta a coluna nome_chapa no cabeçalho.', $erros);
        $this->assertContains('As colunas “Presidente” e “Presidente” são o mesmo cargo.', $erros);
    }

    public function test_linha_com_mais_colunas_que_o_cabecalho(): void
    {
        $this->comCargos();
        $this->conferir($this->fixture('colunas-a-mais.csv'));

        $this->get('/admin/chapas/importar/conferir')->assertSee('A linha tem mais colunas que o cabeçalho');
    }

    public function test_arquivos_que_nao_servem(): void
    {
        $casos = [
            'vazio' => ['', 'O arquivo está vazio.'],
            'só o cabeçalho' => [$this->fixture('so-cabecalho.csv'), 'O arquivo só tem o cabeçalho: nenhuma chapa para importar.'],
            'planilha .xlsx renomeada' => ["PK\x03\x04\x14\x00\x06\x00".str_repeat("\0", 20), 'O arquivo não é um CSV de texto'],
            'sem coluna de cargo' => ["numero_chapa;nome_chapa\n1;Chapa\n", 'O cabeçalho não tem nenhuma coluna de cargo'],
            'coluna sem título com dados' => ["numero_chapa;nome_chapa;Presidente;\n1;Chapa;2024101;2024102\n", 'A coluna 4 tem dados, mas não tem título no cabeçalho.'],
            'separador tab' => ["numero_chapa\tnome_chapa\tPresidente\n1\tChapa\t2024101\n", 'Falta a coluna numero_chapa no cabeçalho.'],
        ];

        foreach ($casos as $caso => [$conteudo, $mensagem]) {
            $this->enviar($conteudo)->assertSessionHasErrors('arquivo');
            $this->assertStringContainsString($mensagem, implode(' ', session('errors')->get('arquivo')), $caso);
        }

        $this->assertSame(0, Chapa::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_arquivo_que_nao_e_csv_e_recusado_pela_extensao(): void
    {
        $this->post('/admin/chapas/importar', ['arquivo' => UploadedFile::fake()->create('chapas.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors(['arquivo' => 'Envie um arquivo .csv (no Excel: Salvar como → CSV UTF-8).']);
    }

    public function test_valores_mal_formatados_numa_linha(): void
    {
        $this->comCargos();
        $csv = "numero_chapa;nome_chapa;Presidente;Vice-presidente;Tesoureiro;contato_email;contato_telefone\n"
            ."0;Chapa Zero;2024101;2024102;2024103;nao-e-email;abc\n"
            ."1,0;Chapa Excel;2024104;2024 105;2024106;;\n"
            ."07;Chapa Sete;2024107;2024108;2024/109;;\n";

        $this->conferir($csv);

        $this->get('/admin/chapas/importar/conferir')
            ->assertSee('Número da chapa “0” inválido', false)
            ->assertSee('E-mail “nao-e-email” inválido.', false)
            ->assertSee('Telefone “abc” inválido.', false)
            ->assertSee('Matrícula “2024/109” em formato inválido', false)
            ->assertSee('Aluno 105'); // "2024 105" vira 2024105; "1,0" vira 1; "07" vira 7.
    }

    // Período e acesso -----------------------------------------------------------------------

    public function test_fora_do_periodo_de_inscricao_nao_importa(): void
    {
        $this->eleicao->update(Eleicao::factory()->votacaoAberta()->raw());

        $this->get('/admin/chapas/importar')->assertOk()->assertSee('Importação indisponível')->assertDontSee('Enviar para conferência');
        $this->enviar($this->fixture('limpo.csv'))->assertSessionHasErrors('arquivo');
    }

    public function test_periodo_que_fecha_durante_a_conferencia_e_conferido_na_confirmacao(): void
    {
        $this->comCargos();
        $assinatura = $this->conferir($this->fixture('limpo.csv'));

        $this->travel(3)->weeks();

        $this->confirmar($assinatura, 'importar')->assertSessionHasErrors('acao');
        $this->assertSame(0, Chapa::count());
    }

    public function test_mesario_nao_acessa_a_importacao(): void
    {
        $this->actingAs(User::factory()->create(['perfil' => 'mesario']));

        $this->get('/admin/chapas/importar')->assertForbidden();
        $this->enviar($this->fixture('limpo.csv'))->assertForbidden();
    }
}
