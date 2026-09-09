<?php

namespace App\Support;

/**
 * EXEMPLO de classe pura (sem banco, sem Laravel): alvo ideal de teste unitário.
 * A apuração completa (vencedora, participação, brancos/nulos) é a história H10.
 */
final class Apuracao
{
    /**
     * @param  array<int|string, int>  $votosPorChapa  ex.: [1 => 40, 2 => 35, 3 => 25]
     * @return array<int|string, float> percentual de cada chapa (0–100, 1 casa decimal)
     */
    public static function percentuais(array $votosPorChapa): array
    {
        $total = array_sum($votosPorChapa);
        if ($total === 0) {
            return array_map(fn () => 0.0, $votosPorChapa);
        }

        return array_map(fn (int $v) => round($v * 100 / $total, 1), $votosPorChapa);
    }
}
