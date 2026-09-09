<?php

namespace App\Support;

/**
 * Regras de apuração puras (sem banco): recebem números, devolvem números.
 * Por não depender do Laravel nem do banco, é o alvo ideal de TESTE UNITÁRIO.
 */
final class Apuracao
{
    /**
     * @param  array<int|string, int>  $votosPorChapa  ex.: [1 => 40, 2 => 35, 3 => 25]
     * @return array<int|string, float> percentual de cada chapa sobre os votos válidos (0–100, 1 casa decimal)
     */
    public static function percentuais(array $votosPorChapa): array
    {
        $total = array_sum($votosPorChapa);
        if ($total === 0) {
            return array_map(fn () => 0.0, $votosPorChapa);
        }

        return array_map(fn (int $v) => round($v * 100 / $total, 1), $votosPorChapa);
    }

    /**
     * Chapa vencedora (chave do array) ou null em caso de empate ou sem votos.
     *
     * @param  array<int|string, int>  $votosPorChapa
     */
    public static function vencedora(array $votosPorChapa): int|string|null
    {
        if ($votosPorChapa === [] || max($votosPorChapa) === 0) {
            return null;
        }

        $maior = max($votosPorChapa);
        $empatadas = array_keys($votosPorChapa, $maior, true);

        return count($empatadas) === 1 ? $empatadas[0] : null;
    }

    /** Participação em % (0–100, 1 casa decimal). */
    public static function participacao(int $votantes, int $aptos): float
    {
        if ($aptos <= 0) {
            return 0.0;
        }

        return round(min($votantes, $aptos) * 100 / $aptos, 1);
    }
}
