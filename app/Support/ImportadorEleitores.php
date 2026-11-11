<?php

namespace App\Support;

use App\Models\Eleitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Importa o eleitorado de um CSV (H4). Formato em docs/MODELO_DADOS.md ("Formato do CSV de eleitores").
 *
 * - Cabeçalho obrigatório com `matricula` e `nome`; `turma` e `email` são opcionais.
 *   Acentos, maiúsculas e hífen no cabeçalho são ignorados ("Matrícula", "E-mail").
 * - Separador `;` (Excel em português) ou `,`, detectado pelo cabeçalho.
 * - UTF-8, com ou sem BOM. Arquivo salvo pelo Excel em Windows-1252 também é aceito.
 * - Cada linha vale por si: linha inválida entra no relatório e as outras são gravadas.
 * - Matrícula que já existe ATUALIZA o cadastro (nome; turma e e-mail se vierem preenchidos).
 */
class ImportadorEleitores
{
    /** Máximo de falhas guardadas no relatório (o total continua sendo contado). */
    public const MAX_FALHAS_LISTADAS = 200;

    /**
     * @return array{criados: int, atualizados: int, total_falhas: int, falhas: list<array{linha: int, matricula: string, motivo: string}>}
     *
     * @throws InvalidArgumentException quando o arquivo não tem o cabeçalho esperado
     */
    public function importar(string $conteudo): array
    {
        $linhas = $this->ler($conteudo);
        $cabecalho = $this->colunas(array_shift($linhas) ?? []);

        $relatorio = ['criados' => 0, 'atualizados' => 0, 'total_falhas' => 0, 'falhas' => []];

        DB::transaction(function () use ($linhas, $cabecalho, &$relatorio) {
            foreach ($linhas as $indice => $campos) {
                $numeroLinha = $indice + 2; // +1 do cabeçalho, +1 porque a planilha começa em 1

                if (implode('', array_map('trim', $campos)) === '') {
                    continue;
                }

                $dados = [];
                foreach ($cabecalho as $posicao => $coluna) {
                    $dados[$coluna] = Str::squish($campos[$posicao] ?? '');
                }

                $erro = $this->validar($dados);
                if ($erro !== null) {
                    $relatorio['total_falhas']++;
                    if (count($relatorio['falhas']) < self::MAX_FALHAS_LISTADAS) {
                        $relatorio['falhas'][] = ['linha' => $numeroLinha, 'matricula' => $dados['matricula'], 'motivo' => $erro];
                    }

                    continue;
                }

                $eleitor = Eleitor::firstOrNew(['matricula' => $dados['matricula']]);
                $eleitor->nome = $dados['nome'];
                foreach (['turma', 'email'] as $opcional) {
                    if (($dados[$opcional] ?? '') !== '') {
                        $eleitor->{$opcional} = $dados[$opcional];
                    }
                }

                $relatorio[$eleitor->exists ? 'atualizados' : 'criados']++;
                $eleitor->save();
            }
        });

        return $relatorio;
    }

    /** @return list<list<string>> */
    private function ler(string $conteudo): array
    {
        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);

        if (! mb_check_encoding($conteudo, 'UTF-8')) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
        }

        $primeiraLinha = strtok($conteudo, "\r\n") ?: '';
        $separador = substr_count($primeiraLinha, ';') >= substr_count($primeiraLinha, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $conteudo);
        rewind($stream);

        $linhas = [];
        while (($campos = fgetcsv($stream, null, $separador, '"', '')) !== false) {
            $linhas[] = array_map(fn ($campo) => (string) $campo, $campos);
        }
        fclose($stream);

        return $linhas;
    }

    /**
     * Posição de cada coluna conhecida no cabeçalho. Colunas desconhecidas são ignoradas.
     *
     * @return array<int, string>
     */
    private function colunas(array $cabecalho): array
    {
        $conhecidas = ['matricula', 'nome', 'turma', 'email'];
        $colunas = [];

        foreach ($cabecalho as $posicao => $titulo) {
            $normalizado = preg_replace('/[^a-z]/', '', Str::lower(Str::ascii((string) $titulo)));
            if (in_array($normalizado, $conhecidas, true) && ! in_array($normalizado, $colunas, true)) {
                $colunas[$posicao] = $normalizado;
            }
        }

        $faltando = array_diff(['matricula', 'nome'], $colunas);
        if ($faltando !== []) {
            throw new InvalidArgumentException(
                'O cabeçalho precisa ter as colunas matricula e nome (faltou: '.implode(', ', $faltando).'). Baixe o modelo para conferir o formato.'
            );
        }

        return $colunas;
    }

    /** Primeira mensagem de erro de cada campo, ou null se a linha é válida. */
    private function validar(array $dados): ?string
    {
        $validador = Validator::make($dados, EleitorRegras::campos(), EleitorRegras::mensagens(), EleitorRegras::nomes());

        if ($validador->passes()) {
            return null;
        }

        return collect($validador->errors()->messages())->map(fn (array $erros) => $erros[0])->implode(' ');
    }
}
