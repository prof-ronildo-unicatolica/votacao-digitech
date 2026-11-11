<?php

namespace App\Support;

use App\Enums\AcaoAuditoria;
use App\Models\Eleitor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Importa o eleitorado de um CSV (H4), no fluxo da D19: `analisar` confere sem gravar, o admin
 * vê cada campo com erro e escolhe cancelar, ignorar as linhas com erro ou confirmar; `importar`
 * grava numa transação só e desfaz tudo se o cadastro mudou desde a conferência.
 * Formato em docs/MODELO_DADOS.md ("Formato do CSV de eleitores").
 *
 * - Cabeçalho com `matricula` e `nome`; `turma` e `email` são opcionais. Acentos, maiúsculas,
 *   hífen e apelidos são aceitos ("Matrícula", "Nome do aluno", "E-mail"). Outras colunas são
 *   ignoradas, e a conferência diz quais.
 * - Leitura por LeitorCsv: `;` ou `,`, BOM, Windows-1252, CRLF.
 * - Nome padronizado por Nomes::formatar ("ANA DA SILVA" → "Ana da Silva"); e-mail em minúsculas.
 * - Matrícula que já existe ATUALIZA o cadastro: o nome sempre; turma e e-mail só se vierem
 *   preenchidos (célula vazia não apaga). Matrícula repetida no arquivo marca todas as linhas.
 */
class ImportadorEleitores
{
    public const MAX_LINHAS = 5000;

    public const COLUNAS = [
        'matricula' => ['ra', 'registro', 'matriculadoaluno'],
        'nome' => ['nomedoaluno', 'aluno', 'nomecompleto', 'estudante'],
        'turma' => ['classe', 'periodo', 'curso', 'semestre'],
        'email' => ['emaildoaluno', 'emailinstitucional', 'correioeletronico'],
    ];

    /** Rótulo de cada coluna na tela e nos erros. */
    public const ROTULOS = ['matricula' => 'matrícula', 'nome' => 'nome', 'turma' => 'turma', 'email' => 'e-mail'];

    /**
     * @throws ArquivoInvalido quando o arquivo inteiro não serve (cabeçalho, tamanho, binário)
     */
    public function analisar(string $conteudo): AnaliseEleitores
    {
        try {
            $linhas = LeitorCsv::linhas($conteudo);
        } catch (\InvalidArgumentException $e) {
            throw new ArquivoInvalido([$e->getMessage()]);
        }

        $numeroLinha = 1;
        while ($linhas !== [] && LeitorCsv::vazia($linhas[0])) {
            array_shift($linhas);
            $numeroLinha++;
        }

        $cabecalho = array_shift($linhas) ?? [];
        if (LeitorCsv::vazia($cabecalho)) {
            throw new ArquivoInvalido(['O arquivo está vazio.']);
        }

        try {
            $colunas = LeitorCsv::colunas($cabecalho, self::COLUNAS, ['matricula', 'nome']);
        } catch (\InvalidArgumentException $e) {
            throw new ArquivoInvalido([$e->getMessage()]);
        }
        $ignoradas = array_values(array_filter(
            array_map('trim', array_diff_key($cabecalho, $colunas)),
            fn ($titulo) => $titulo !== '',
        ));

        $dados = [];
        $emBranco = 0;
        foreach ($linhas as $indice => $campos) {
            if (LeitorCsv::vazia($campos)) {
                $emBranco++;

                continue;
            }
            $dados[$numeroLinha + 1 + $indice] = $campos;
        }

        if ($dados === []) {
            throw new ArquivoInvalido(['O arquivo só tem o cabeçalho: nenhum aluno para importar.']);
        }
        if (count($dados) > self::MAX_LINHAS) {
            throw new ArquivoInvalido(['O arquivo tem '.count($dados).' linhas; o máximo por importação é '.self::MAX_LINHAS.'. Divida em mais de um arquivo.']);
        }

        $resultado = [];
        foreach ($dados as $linha => $campos) {
            $resultado[] = $this->linha($linha, $campos, count($cabecalho), $colunas);
        }

        $resultado = $this->comCadastro($this->repetidasNoArquivo($resultado));

        return new AnaliseEleitores($resultado, $ignoradas, $emBranco);
    }

    /**
     * Grava as linhas válidas, todas ou nenhuma.
     *
     * @return array{criados: int, atualizados: int, iguais: int, ignoradas: list<array>}
     *
     * @throws ImportacaoDesatualizada quando o resultado mudou desde a conferência
     * @throws ImportacaoRecusada com erros que o admin não mandou ignorar
     * @throws ArquivoInvalido
     */
    public function importar(string $conteudo, string $assinatura, bool $ignorarErros, string $arquivo = 'arquivo.csv'): array
    {
        $mudou = 'O cadastro mudou desde a conferência (por exemplo, um aluno foi cadastrado ou editado em outra tela). Nada foi gravado: confira o resultado de novo antes de confirmar.';

        try {
            return DB::transaction(function () use ($conteudo, $assinatura, $ignorarErros, $mudou, $arquivo) {
                $analise = $this->analisar($conteudo);

                if (! hash_equals($analise->assinatura(), $assinatura)) {
                    throw new ImportacaoDesatualizada($mudou);
                }
                if ($analise->comErro() !== [] && ! $ignorarErros) {
                    throw new ImportacaoRecusada('Há linhas com erro. Corrija o arquivo, ou escolha ignorar as linhas com erro.');
                }
                if ($analise->validas() === []) {
                    throw new ImportacaoRecusada('Nenhuma linha do arquivo está pronta para importar.');
                }

                foreach ($analise->com('novo') as $linha) {
                    Eleitor::create(['matricula' => $linha['matricula'], 'nome' => $linha['nome'], 'turma' => $linha['turma'], 'email' => $linha['email']]);
                }
                foreach ($analise->com('atualizar') as $linha) {
                    Eleitor::whereRaw('LOWER(matricula) = ?', [Str::lower($linha['matricula'])])->firstOrFail()
                        ->update(array_map(fn (array $mudanca) => $mudanca[1], $linha['mudancas']));
                }

                // H11 fase 2: o nome e o hash do arquivo, nunca o conteúdo (D22).
                Auditoria::registrar(AcaoAuditoria::EleitoresImportados, dados: [
                    'arquivo' => $arquivo,
                    'sha256' => hash('sha256', $conteudo),
                    'criados' => count($analise->com('novo')),
                    'atualizados' => count($analise->com('atualizar')),
                    'iguais' => count($analise->com('igual')),
                    'ignoradas' => count($analise->comErro()),
                ]);

                return [
                    'criados' => count($analise->com('novo')),
                    'atualizados' => count($analise->com('atualizar')),
                    'iguais' => count($analise->com('igual')),
                    'ignoradas' => array_map(fn (array $l) => ['linha' => $l['linha'], 'matricula' => $l['matricula'], 'erros' => $l['erros']], $analise->comErro()),
                ];
            });
        } catch (UniqueConstraintViolationException) {
            throw new ImportacaoDesatualizada($mudou);
        }
    }

    /** Limpa e valida uma linha com as regras do formulário (EleitorRegras). */
    private function linha(int $linha, array $campos, int $largura, array $colunas): array
    {
        $bruto = [];
        foreach ($colunas as $posicao => $coluna) {
            $bruto[$coluna] = (string) ($campos[$posicao] ?? '');
        }

        $dados = [
            'matricula' => preg_replace('/\s+/', '', $bruto['matricula']),
            'nome' => Nomes::formatar($bruto['nome'], siglas: false),
            'turma' => Str::squish($bruto['turma'] ?? ''),
            'email' => Str::lower(Str::squish($bruto['email'] ?? '')),
        ];

        $erros = [];
        if (count($campos) > $largura && ! LeitorCsv::vazia(array_slice($campos, $largura))) {
            $erros[] = ['coluna' => null, 'motivo' => 'A linha tem mais colunas que o cabeçalho. Confira o separador, ou se algum nome tem ; ou , sem estar entre aspas.'];
        }

        if (preg_match('/^\d+([.,]\d+)?e\+?\d+$/i', $dados['matricula'])) {
            $erros[] = ['coluna' => 'matricula', 'motivo' => "A matrícula “{$dados['matricula']}” virou notação científica no Excel. Formate a coluna como Texto e exporte de novo."];
        }

        $validador = Validator::make(array_map(fn ($v) => $v === '' ? null : $v, $dados), EleitorRegras::campos(), EleitorRegras::mensagens(), EleitorRegras::nomes());
        foreach ($validador->errors()->messages() as $coluna => $mensagens) {
            if ($coluna === 'matricula' && $erros !== [] && end($erros)['coluna'] === 'matricula') {
                continue; // já explicou a notação científica
            }
            $erros[] = ['coluna' => $coluna, 'motivo' => $mensagens[0]];
        }

        return [
            'linha' => $linha,
            'matricula' => $dados['matricula'],
            'nome' => $dados['nome'],
            'nome_original' => $bruto['nome'],
            'turma' => $dados['turma'] !== '' ? $dados['turma'] : null,
            'email' => $dados['email'] !== '' ? $dados['email'] : null,
            'situacao' => 'novo',
            'mudancas' => [],
            'erros' => $erros,
        ];
    }

    /** Matrícula repetida no arquivo: qual das linhas vale? A comissão decide. Marca todas. */
    private function repetidasNoArquivo(array $linhas): array
    {
        $porMatricula = [];
        foreach ($linhas as $i => $linha) {
            if ($linha['matricula'] !== '') {
                $porMatricula[Str::lower($linha['matricula'])][] = $i;
            }
        }

        foreach ($porMatricula as $indices) {
            if (count($indices) < 2) {
                continue;
            }
            foreach ($indices as $i) {
                $outras = array_map(fn ($j) => $linhas[$j]['linha'], array_values(array_diff($indices, [$i])));
                $linhas[$i]['erros'][] = ['coluna' => 'matricula', 'motivo' => "A matrícula {$linhas[$i]['matricula']} se repete neste arquivo (linha ".Nomes::lista($outras).').'];
            }
        }

        return $linhas;
    }

    /** Novo, atualizar (com o antes e depois de cada campo) ou igual ao que já está cadastrado. */
    private function comCadastro(array $linhas): array
    {
        $matriculas = array_values(array_unique(array_filter(array_column($linhas, 'matricula'))));
        // Array simples, não Collection::merge: merge renumera chaves numéricas ("2024101").
        $cadastrados = [];
        foreach (array_chunk($matriculas, 500) as $lote) {
            foreach (Eleitor::whereIn('matricula', $lote)->get(['matricula', 'nome', 'turma', 'email']) as $eleitor) {
                $cadastrados[Str::lower($eleitor->matricula)] = $eleitor;
            }
        }

        foreach ($linhas as &$linha) {
            $eleitor = $cadastrados[Str::lower($linha['matricula'])] ?? null;
            if (! $eleitor) {
                continue;
            }

            $mudancas = [];
            if ($eleitor->nome !== $linha['nome']) {
                $mudancas['nome'] = [$eleitor->nome, $linha['nome']];
            }
            foreach (['turma', 'email'] as $opcional) {
                if ($linha[$opcional] !== null && $eleitor->{$opcional} !== $linha[$opcional]) {
                    $mudancas[$opcional] = [$eleitor->{$opcional}, $linha[$opcional]];
                }
            }

            $linha['situacao'] = $mudancas === [] ? 'igual' : 'atualizar';
            $linha['mudancas'] = $mudancas;
        }

        return $linhas;
    }
}
