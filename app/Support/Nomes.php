<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Padronização de nomes vindos de planilha (cargos e chapas, H15; alunos, H4).
 *
 * - `formatar`: tira espaços sobrando, troca "1o"/"1°" por "1º" e arruma maiúsculas e
 *   minúsculas quando o texto veio TODO em maiúsculas ou todo em minúsculas
 *   ("VICE-PRESIDENTE" → "Vice-presidente", "chapa da mudança" → "Chapa da Mudança").
 *   Texto que já mistura maiúsculas e minúsculas ("DCE Livre") é respeitado.
 *   Vale para cargos e chapas (H15) e para o nome dos alunos (H4).
 * - `chave`: forma de comparar dois nomes ignorando acentos, maiúsculas, espaços e pontuação
 *   ("vice presidente" e "Vice-Presidente" são o mesmo cargo).
 */
class Nomes
{
    /** Palavras que ficam em minúsculas no meio do nome. */
    private const CONECTIVOS = ['a', 'à', 'as', 'ao', 'aos', 'o', 'os', 'e', 'de', 'da', 'das', 'do', 'dos', 'em', 'na', 'nas', 'no', 'nos', 'para', 'por', 'com'];

    /** @param bool $siglas palavra curta em CAIXA ALTA fica como está (chapas: "UNE"); para nome de pessoa, false ("LUZ" → "Luz") */
    public static function formatar(string $texto, bool $siglas = true): string
    {
        $texto = Str::squish($texto);
        $texto = preg_replace('/\b(\d+)[oº°](?=\s|$)/u', '$1º', $texto);
        $texto = preg_replace('/\b(\d+)[aª](?=\s|$)/u', '$1ª', $texto);

        $maiusculas = $texto === mb_strtoupper($texto);
        $minusculas = $texto === mb_strtolower($texto);

        if (! $maiusculas && ! $minusculas) {
            return self::primeiraMaiuscula($texto);
        }

        $palavras = explode(' ', $texto);
        foreach ($palavras as $i => $palavra) {
            $minuscula = mb_strtolower($palavra);

            if ($i > 0 && in_array($minuscula, self::CONECTIVOS, true)) {
                $palavras[$i] = $minuscula;
            } elseif ($siglas && $maiusculas && $i > 0 && mb_strlen(preg_replace('/[^\p{L}]/u', '', $palavra)) <= 3 && preg_match('/\p{L}/u', $palavra)) {
                $palavras[$i] = $palavra; // sigla no meio: "CHAPA DCE" → "Chapa DCE"
            } else {
                $palavras[$i] = self::primeiraMaiuscula($minuscula);
            }
        }

        return implode(' ', $palavras);
    }

    public static function chave(string $texto): string
    {
        $texto = str_replace(['º', '°', 'ª'], ['o', 'o', 'a'], $texto);

        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($texto)));
    }

    /** "a, b e c" */
    public static function lista(array $itens): string
    {
        $itens = array_values($itens);
        $ultimo = array_pop($itens);

        return $itens === [] ? (string) $ultimo : implode(', ', $itens).' e '.$ultimo;
    }

    private static function primeiraMaiuscula(string $texto): string
    {
        return preg_replace_callback('/\p{L}/u', fn ($letra) => mb_strtoupper($letra[0]), $texto, 1);
    }
}
