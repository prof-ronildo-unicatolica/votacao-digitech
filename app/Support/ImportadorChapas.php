<?php

namespace App\Support;

use App\Enums\OrigemChapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa chapas de um CSV (H15, D19). Formato em docs/MODELO_DADOS.md ("Formato do CSV de chapas"):
 * uma linha por chapa e uma coluna por cargo, com a matrícula do candidato.
 *
 *     numero_chapa;nome_chapa;Presidente;Vice-presidente;Tesoureiro
 *     1;Movimento Estudantil;2024101;2024102;2024103
 *
 * Em duas etapas:
 * 1. `analisar`: lê e confere tudo SEM gravar. O admin vê cada campo com erro e escolhe:
 *    cancelar (nada é gravado), ignorar as chapas com erro ou confirmar.
 * 2. `importar`: grava as chapas válidas numa transação só, travando a eleição como a
 *    inscrição pelo formulário (InscricaoDeChapa). Se o resultado mudou desde a conferência,
 *    ou qualquer gravação falhar, a transação inteira é desfeita (rollback).
 *
 * As colunas que não são de chapa são os CARGOS. Se a eleição ainda não tem cargos, o
 * cabeçalho os cria (na ordem das colunas); se já tem, as colunas precisam ser exatamente
 * os cargos da eleição. Nomes são comparados por Nomes::chave ("VICE PRESIDENTE" é
 * "Vice-presidente") e padronizados por Nomes::formatar.
 */
class ImportadorChapas
{
    public const MAX_LINHAS = 500;

    public const MAX_CARGOS = 20;

    /** Colunas da chapa e os apelidos aceitos no cabeçalho (comparados por Nomes::chave). */
    public const COLUNAS = [
        'numero_chapa' => ['numero', 'numerodachapa', 'nchapa', 'no', 'n', 'chapanumero'],
        'nome_chapa' => ['nome', 'nomedachapa', 'chapa', 'chapanome'],
        'contato_email' => ['email', 'emailcontato', 'emaildocontato', 'emaildoresponsavel'],
        'contato_telefone' => ['telefone', 'telefonecontato', 'celular', 'whatsapp', 'telefonedoresponsavel'],
    ];

    public function __construct(private readonly InscricaoDeChapa $inscricao) {}

    /**
     * @throws ArquivoInvalido quando o arquivo inteiro não serve (cabeçalho, cargos, tamanho)
     */
    public function analisar(Eleicao $eleicao, string $conteudo): AnaliseImportacao
    {
        try {
            $linhas = LeitorCsv::linhas($conteudo);
        } catch (\InvalidArgumentException $e) {
            throw new ArquivoInvalido([$e->getMessage()]);
        }

        // Linhas em branco no começo do arquivo não contam como cabeçalho.
        $numeroLinha = 1;
        while ($linhas !== [] && LeitorCsv::vazia($linhas[0])) {
            array_shift($linhas);
            $numeroLinha++;
        }

        $cabecalho = array_shift($linhas) ?? [];
        if (LeitorCsv::vazia($cabecalho)) {
            throw new ArquivoInvalido(['O arquivo está vazio.']);
        }
        [$colunas, $cargos, $ajustes] = $this->cabecalho($eleicao, $cabecalho, $linhas);

        $dados = [];
        $linhasEmBranco = 0;
        foreach ($linhas as $indice => $campos) {
            if (LeitorCsv::vazia($campos)) {
                $linhasEmBranco++;

                continue;
            }
            $dados[$numeroLinha + 1 + $indice] = $campos;
        }

        if ($dados === []) {
            throw new ArquivoInvalido(['O arquivo só tem o cabeçalho: nenhuma chapa para importar.']);
        }
        if (count($dados) > self::MAX_LINHAS) {
            throw new ArquivoInvalido(['O arquivo tem '.count($dados).' chapas; o máximo por importação é '.self::MAX_LINHAS.'.']);
        }

        $nomesDosAlunos = Eleitor::whereIn('matricula', $this->todasAsMatriculas($dados, $cargos))->pluck('nome', 'matricula');
        $numerosUsados = $eleicao->chapas()->pluck('numero')->flip();

        $chapas = [];
        foreach ($dados as $linha => $campos) {
            $chapa = $this->linha($eleicao, $linha, $campos, count($cabecalho), $colunas, $cargos, $nomesDosAlunos, $numerosUsados);
            if ($chapa['nome'] !== '' && $chapa['nome'] !== Str::squish($chapa['nome_original'])) {
                $ajustes[] = "Linha {$linha}: chapa “".Str::squish($chapa['nome_original'])."” → “{$chapa['nome']}”.";
            }
            $chapas[] = $chapa;
        }

        $chapas = $this->emOrdemDeColuna($this->repetidasNoArquivo($chapas, $cargos), $cargos);

        return new AnaliseImportacao(array_values($cargos), $eleicao->cargos()->doesntExist(), $chapas, $ajustes, $linhasEmBranco);
    }

    /**
     * Grava as chapas válidas, todas ou nenhuma.
     *
     * @param  string  $assinatura  a da análise que o admin conferiu (AnaliseImportacao::assinatura)
     * @param  bool  $ignorarErros  o admin escolheu importar só as válidas e deixar as com erro de fora
     * @return array{importadas: list<array{numero: int, nome: string}>, ignoradas: list<array>, cargos_criados: list<string>}
     *
     * @throws InscricaoRecusada fora do período, ou com erros que o admin não mandou ignorar
     * @throws ImportacaoDesatualizada quando o resultado mudou desde a conferência
     * @throws ArquivoInvalido
     */
    public function importar(Eleicao $eleicao, string $conteudo, string $assinatura, bool $ignorarErros): array
    {
        try {
            return DB::transaction(function () use ($eleicao, $conteudo, $assinatura, $ignorarErros) {
                $eleicao = $this->inscricao->travar($eleicao);
                $analise = $this->analisar($eleicao, $conteudo);

                if (! hash_equals($analise->assinatura(), $assinatura)) {
                    throw new ImportacaoDesatualizada;
                }
                if ($analise->comErro() !== [] && ! $ignorarErros) {
                    throw new InscricaoRecusada(['geral' => 'Há chapas com erro. Corrija o arquivo, ou escolha ignorar as chapas com erro.']);
                }
                if ($analise->validas() === []) {
                    throw new InscricaoRecusada(['geral' => 'Nenhuma chapa do arquivo está pronta para importar.']);
                }

                // Cabeçalho cria os cargos, na ordem das colunas (só quando a eleição não tem nenhum).
                $idDoCargo = [];
                foreach ($analise->cargos as $ordem => $cargo) {
                    $idDoCargo[$cargo['chave']] = $cargo['existe']
                        ? $cargo['chave']
                        : $eleicao->cargos()->create(['titulo' => $cargo['titulo'], 'ordem' => $ordem + 1])->id;
                }

                $importadas = [];
                foreach ($analise->validas() as $chapa) {
                    $eleitores = [];
                    foreach ($chapa['candidatos'] as $chave => $candidato) {
                        $eleitores[$idDoCargo[$chave]] = $candidato['eleitor_id'];
                    }

                    $this->inscricao->criar($eleicao, $chapa['nome'], $chapa['numero'], $eleitores, $chapa['contato_email'], $chapa['contato_telefone'], OrigemChapa::Csv);
                    $importadas[] = ['numero' => $chapa['numero'], 'nome' => $chapa['nome']];
                }

                return [
                    'importadas' => $importadas,
                    'ignoradas' => array_map(fn (array $chapa) => [
                        'linha' => $chapa['linha'],
                        'numero' => $chapa['numero_bruto'],
                        'nome' => $chapa['nome'],
                        'erros' => $chapa['erros'],
                    ], $analise->comErro()),
                    'cargos_criados' => $analise->criaCargos ? array_column($analise->cargos, 'titulo') : [],
                ];
            });
        } catch (UniqueConstraintViolationException) {
            // O banco recusou algo que a conferência não viu: tudo foi desfeito.
            throw new ImportacaoDesatualizada;
        }
    }

    /**
     * Separa as colunas da chapa das colunas de cargo e confere os cargos com os da eleição.
     *
     * @return array{0: array<int, string>, 1: array<string, array>, 2: list<string>} colunas da chapa (posição => nome), cargos (chave => cargo) e ajustes
     */
    private function cabecalho(Eleicao $eleicao, array $cabecalho, array $linhas): array
    {
        $erros = [];
        $ajustes = [];
        $colunas = [];
        $doArquivo = [];

        foreach ($cabecalho as $posicao => $titulo) {
            $titulo = Str::squish($titulo);
            $chave = Nomes::chave($titulo);

            if ($chave === '') {
                $temDados = collect($linhas)->contains(fn (array $campos) => trim($campos[$posicao] ?? '') !== '');
                if ($temDados) {
                    $erros[] = 'A coluna '.($posicao + 1).' tem dados, mas não tem título no cabeçalho.';
                }

                continue;
            }

            $reservada = collect(self::COLUNAS)->search(fn (array $apelidos, string $coluna) => $chave === Nomes::chave($coluna) || in_array($chave, $apelidos, true));
            if ($reservada !== false) {
                if (in_array($reservada, $colunas, true)) {
                    $erros[] = "A coluna {$reservada} aparece duas vezes no cabeçalho.";
                }
                $colunas[$posicao] = $reservada;

                continue;
            }

            if (isset($doArquivo[$chave])) {
                $erros[] = "As colunas “{$doArquivo[$chave]['coluna']}” e “{$titulo}” são o mesmo cargo.";

                continue;
            }
            $doArquivo[$chave] = ['coluna' => $titulo, 'posicao' => $posicao, 'titulo' => Nomes::formatar($titulo)];
        }

        foreach (['numero_chapa', 'nome_chapa'] as $obrigatoria) {
            if (! in_array($obrigatoria, $colunas, true)) {
                $erros[] = "Falta a coluna {$obrigatoria} no cabeçalho.";
            }
        }

        $existentes = $eleicao->cargos()->get();
        if ($doArquivo === []) {
            $erros[] = 'O cabeçalho não tem nenhuma coluna de cargo. Depois de numero_chapa e nome_chapa, use uma coluna por cargo (ex.: Presidente;Vice-presidente).';
        } elseif (count($doArquivo) > self::MAX_CARGOS) {
            $erros[] = 'O cabeçalho tem '.count($doArquivo).' colunas de cargo; o máximo é '.self::MAX_CARGOS.'.';
        }

        $cargos = [];
        if ($existentes->isEmpty()) {
            // A eleição não tem cargos: o cabeçalho cria, na ordem das colunas.
            foreach ($doArquivo as $chave => $coluna) {
                if (mb_strlen($coluna['titulo']) > 80) {
                    $erros[] = "O cargo “{$coluna['coluna']}” tem mais de 80 caracteres.";
                }
                if ($coluna['titulo'] !== $coluna['coluna']) {
                    $ajustes[] = "Cargo “{$coluna['coluna']}” → “{$coluna['titulo']}”.";
                }
                $cargos["novo:{$chave}"] = ['chave' => "novo:{$chave}", 'titulo' => $coluna['titulo'], 'coluna' => $coluna['coluna'], 'posicao' => $coluna['posicao'], 'existe' => false];
            }
        } else {
            // A eleição já tem cargos (H13): as colunas precisam ser exatamente esses cargos.
            foreach ($existentes as $cargo) {
                $coluna = $doArquivo[Nomes::chave($cargo->titulo)] ?? null;
                if (! $coluna) {
                    $erros[] = "Falta a coluna do cargo {$cargo->titulo}.";

                    continue;
                }
                if ($coluna['coluna'] !== $cargo->titulo) {
                    $ajustes[] = "Coluna “{$coluna['coluna']}” → cargo {$cargo->titulo}.";
                }
                $cargos[$cargo->id] = ['chave' => $cargo->id, 'titulo' => $cargo->titulo, 'coluna' => $coluna['coluna'], 'posicao' => $coluna['posicao'], 'existe' => true];
            }

            $chavesDaEleicao = $existentes->map(fn ($cargo) => Nomes::chave($cargo->titulo))->all();
            foreach ($doArquivo as $chave => $coluna) {
                if (! in_array($chave, $chavesDaEleicao, true)) {
                    $erros[] = "A coluna “{$coluna['coluna']}” não é um cargo desta eleição. Cargos: ".Nomes::lista($existentes->pluck('titulo')->all()).'.';
                }
            }
        }

        if ($erros !== []) {
            throw new ArquivoInvalido($erros);
        }

        return [$colunas, $cargos, $ajustes];
    }

    /** Confere uma linha (uma chapa) com as regras da H14, sem gravar. */
    private function linha(Eleicao $eleicao, int $linha, array $campos, int $largura, array $colunas, array $cargos, $nomesDosAlunos, $numerosUsados): array
    {
        $erros = [];
        $valor = fn (string $coluna) => Str::squish($campos[array_search($coluna, $colunas, true)] ?? '');

        if (count($campos) > $largura && ! LeitorCsv::vazia(array_slice($campos, $largura))) {
            $erros[] = ['coluna' => null, 'motivo' => 'A linha tem mais colunas que o cabeçalho. Confira o separador, ou se algum nome tem ; ou , sem estar entre aspas.'];
        }

        // Número: inteiro a partir de 1. "01" e o "1,0" do Excel valem 1.
        $numeroBruto = $valor('numero_chapa');
        $numero = null;
        if (preg_match('/^0*(\d{1,3})([.,]0+)?$/', $numeroBruto, $partes) && (int) $partes[1] > 0) {
            $numero = (int) $partes[1];
            if ($numerosUsados->has($numero)) {
                $erros[] = ['coluna' => 'numero_chapa', 'motivo' => "Já existe a chapa número {$numero} nesta eleição."];
            }
        } else {
            $erros[] = ['coluna' => 'numero_chapa', 'motivo' => $numeroBruto === ''
                ? 'Informe o número da chapa.'
                : "Número da chapa “{$numeroBruto}” inválido: use um número inteiro a partir de 1."];
        }

        $nomeOriginal = $campos[array_search('nome_chapa', $colunas, true)] ?? '';
        $nome = Nomes::formatar($nomeOriginal);
        if (mb_strlen($nome) > 100) {
            $erros[] = ['coluna' => 'nome_chapa', 'motivo' => 'O nome da chapa tem mais de 100 caracteres.'];
        }

        $email = in_array('contato_email', $colunas, true) ? Str::lower($valor('contato_email')) : '';
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = ['coluna' => 'contato_email', 'motivo' => "E-mail “{$email}” inválido."];
        }
        $telefone = in_array('contato_telefone', $colunas, true) ? $valor('contato_telefone') : '';
        if ($telefone !== '' && ! preg_match('/^[0-9()+\-\s]{8,20}$/', $telefone)) {
            $erros[] = ['coluna' => 'contato_telefone', 'motivo' => "Telefone “{$telefone}” inválido."];
        }

        // Matrícula de cada cargo. Espaços no meio ("2024 101") são tirados.
        $matriculas = [];
        $candidatos = [];
        $cientifica = [];
        foreach ($cargos as $chave => $cargo) {
            $matricula = preg_replace('/\s+/', '', $campos[$cargo['posicao']] ?? '');
            if (preg_match('/^\d+([.,]\d+)?e\+?\d+$/i', $matricula)) {
                $cientifica[$chave] = "A matrícula “{$matricula}” virou notação científica no Excel. Formate a coluna como Texto e exporte de novo.";
            }
            $matriculas[$chave] = $matricula;
            $candidatos[$chave] = ['matricula' => $matricula, 'aluno' => $nomesDosAlunos[$matricula] ?? null, 'eleitor_id' => null];
        }

        // Mesmas regras do formulário (H14): nome livre, um aluno cadastrado por cargo, sem repetir.
        [$errosDaChapa, $alunos] = $this->inscricao->conferir($eleicao, $nome, array_column($cargos, 'titulo', 'chave'), $matriculas, comissao: true);
        foreach ($errosDaChapa as $chave => $motivo) {
            $erros[] = $chave === 'nome'
                ? ['coluna' => 'nome_chapa', 'motivo' => $motivo]
                : ['coluna' => $cargos[$chave]['titulo'], 'motivo' => $cientifica[$chave] ?? $motivo];
        }
        foreach ($alunos as $chave => $aluno) {
            $candidatos[$chave]['eleitor_id'] = $aluno->id;
        }

        return [
            'linha' => $linha,
            'numero' => $numero,
            'numero_bruto' => $numeroBruto,
            'nome' => $nome,
            'nome_original' => $nomeOriginal,
            'contato_email' => $email !== '' ? $email : null,
            'contato_telefone' => $telefone !== '' ? $telefone : null,
            'candidatos' => $candidatos,
            'erros' => $erros,
        ];
    }

    /** Dentro do próprio arquivo: número, nome ou aluno repetido em duas chapas. Marca as DUAS. */
    private function repetidasNoArquivo(array $chapas, array $cargos): array
    {
        $porNumero = [];
        $porNome = [];
        $porMatricula = [];

        foreach ($chapas as $i => $chapa) {
            if ($chapa['numero'] !== null) {
                $porNumero[$chapa['numero']][] = $i;
            }
            if ($chapa['nome'] !== '') {
                $porNome[Nomes::chave($chapa['nome'])][] = $i;
            }
            foreach ($chapa['candidatos'] as $chave => $candidato) {
                if ($candidato['matricula'] !== '' && ! in_array($i, array_column($porMatricula[$candidato['matricula']] ?? [], 'i'), true)) {
                    $porMatricula[$candidato['matricula']][] = ['i' => $i, 'chave' => $chave];
                }
            }
        }

        $outras = fn (array $indices, int $atual) => Nomes::lista(array_map(fn ($j) => $chapas[$j]['linha'], array_values(array_diff($indices, [$atual]))));

        foreach ($porNumero as $numero => $indices) {
            foreach (count($indices) > 1 ? $indices : [] as $i) {
                $chapas[$i]['erros'][] = ['coluna' => 'numero_chapa', 'motivo' => "O número {$numero} se repete neste arquivo (linha ".$outras($indices, $i).').'];
            }
        }
        foreach ($porNome as $indices) {
            foreach (count($indices) > 1 ? $indices : [] as $i) {
                $chapas[$i]['erros'][] = ['coluna' => 'nome_chapa', 'motivo' => 'Outra chapa deste arquivo tem o mesmo nome (linha '.$outras($indices, $i).').'];
            }
        }
        foreach ($porMatricula as $matricula => $ocorrencias) {
            if (count($ocorrencias) < 2) {
                continue;
            }
            $indices = array_column($ocorrencias, 'i');
            foreach ($ocorrencias as ['i' => $i, 'chave' => $chave]) {
                $aluno = $chapas[$i]['candidatos'][$chave]['aluno'] ?? "A matrícula {$matricula}";
                $chapas[$i]['erros'][] = ['coluna' => $cargos[$chave]['titulo'], 'motivo' => "{$aluno} também está em outra chapa deste arquivo (linha ".$outras($indices, $i).').'];
            }
        }

        return $chapas;
    }

    /** Erros de cada linha na ordem das colunas da tela: linha inteira, número, nome, cargos, contato. */
    private function emOrdemDeColuna(array $chapas, array $cargos): array
    {
        $ordem = array_flip(['', 'numero_chapa', 'nome_chapa', ...array_column($cargos, 'titulo'), 'contato_email', 'contato_telefone']);

        foreach ($chapas as &$chapa) {
            usort($chapa['erros'], fn (array $a, array $b) => ($ordem[$a['coluna'] ?? ''] ?? 99) <=> ($ordem[$b['coluna'] ?? ''] ?? 99));
        }

        return $chapas;
    }

    private function todasAsMatriculas(array $dados, array $cargos): array
    {
        $matriculas = [];
        foreach ($dados as $campos) {
            foreach ($cargos as $cargo) {
                $matriculas[] = preg_replace('/\s+/', '', $campos[$cargo['posicao']] ?? '');
            }
        }

        return array_values(array_unique(array_filter($matriculas)));
    }
}
