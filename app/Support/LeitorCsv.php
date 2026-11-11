<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Leitura de CSV comum às importações (eleitores, H4; chapas, H15).
 *
 * - UTF-8, com ou sem BOM. Arquivo salvo pelo Excel em Windows-1252 é convertido.
 * - Separador `;` (Excel em português) ou `,`, detectado pela linha do cabeçalho.
 * - Quebra de linha `\n` ou `\r\n`; campos entre aspas podem ter `;`, `,` e quebra de linha.
 */
class LeitorCsv
{
    /**
     * Todas as linhas do arquivo, cada uma como lista de campos (texto).
     *
     * @return list<list<string>>
     *
     * @throws InvalidArgumentException quando o conteúdo não é texto (ex.: planilha .xlsx renomeada)
     */
    public static function linhas(string $conteudo): array
    {
        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);

        if (str_contains($conteudo, "\0") || str_starts_with($conteudo, "PK\x03\x04")) {
            throw new InvalidArgumentException('O arquivo não é um CSV de texto. No Excel, use Salvar como → CSV UTF-8 (separado por vírgulas).');
        }

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

    /** A linha está vazia (ou só tem separadores, como o Excel grava `;;;`)? */
    public static function vazia(array $campos): bool
    {
        return trim(implode('', $campos)) === '';
    }

    /**
     * Posição de cada coluna conhecida no cabeçalho. Acentos, maiúsculas, espaços e hífen
     * são ignorados ("Matrícula", "E-mail", "Número da chapa"). Colunas desconhecidas ficam de fora.
     *
     * @param  array<string, list<string>>  $conhecidas  nome da coluna => apelidos aceitos (já em Nomes::chave)
     * @param  list<string>  $obrigatorias
     * @return array<int, string> posição => nome da coluna
     *
     * @throws InvalidArgumentException quando falta coluna obrigatória
     */
    public static function colunas(array $cabecalho, array $conhecidas, array $obrigatorias): array
    {
        $colunas = [];

        foreach ($cabecalho as $posicao => $titulo) {
            $chave = Nomes::chave((string) $titulo);
            foreach ($conhecidas as $coluna => $apelidos) {
                if (in_array($chave, [Nomes::chave($coluna), ...$apelidos], true) && ! in_array($coluna, $colunas, true)) {
                    $colunas[$posicao] = $coluna;
                    break;
                }
            }
        }

        $faltando = array_values(array_diff($obrigatorias, $colunas));
        if ($faltando !== []) {
            throw new InvalidArgumentException(
                'O cabeçalho precisa ter as colunas '.Nomes::lista($obrigatorias).' (faltou: '.implode(', ', $faltando).'). Baixe o modelo para conferir o formato.'
            );
        }

        return $colunas;
    }
}
