<?php

namespace App\Support;

/**
 * Cálculos da apuração (H10). Classe PURA: recebe números, devolve números. Sem banco e sem
 * Laravel, para ser testada só com PHPUnit (tests/Unit/ApuracaoTest.php). Quem busca os
 * números no banco é `ResultadoDaEleicao`.
 *
 * - **Válidos** são os votos em chapa. Branco e nulo contam no total e na participação, mas
 *   não decidem a vencedora (D26).
 * - **Vencedora** é a chapa com mais votos. Empate no primeiro lugar não tem vencedora: a
 *   apuração mostra as empatadas, e a comissão decide pelo regimento (D26).
 * - **Conferência:** o total de votos deve ser igual ao número de eleitores que votaram. Se
 *   não for, a tela avisa (H8 grava os dois na mesma transação, então só diverge com erro).
 */
final class Apuracao
{
    /** Votos em chapa. */
    public readonly int $validos;

    /** Votos em chapa + brancos + nulos. */
    public readonly int $total;

    /** @var list<int|string> chapas com a maior votação (mais de uma = empate). Vazia sem votos válidos. */
    public readonly array $primeiras;

    /**
     * @param  array<int|string, int>  $votosPorChapa  chave = chapa (todas as homologadas, mesmo com 0)
     * @param  int  $votaram  eleitores com a sessão "votou" (comparecimento)
     * @param  int  $aptos  eleitores cadastrados
     */
    public function __construct(
        public readonly array $votosPorChapa,
        public readonly int $brancos,
        public readonly int $nulos,
        public readonly int $votaram,
        public readonly int $aptos,
    ) {
        $this->validos = array_sum($votosPorChapa);
        $this->total = $this->validos + $brancos + $nulos;

        $maior = $this->validos > 0 ? max($votosPorChapa) : 0;
        $this->primeiras = $maior > 0 ? array_keys(array_filter($votosPorChapa, fn (int $v) => $v === $maior)) : [];
    }

    /** A chapa vencedora, ou null se não houver votos válidos ou se houver empate. */
    public function vencedora(): int|string|null
    {
        return count($this->primeiras) === 1 ? $this->primeiras[0] : null;
    }

    public function empate(): bool
    {
        return count($this->primeiras) > 1;
    }

    /** @return array<int|string, float> % de cada chapa sobre o total (inclui brancos e nulos no denominador). */
    public function percentuaisDoTotal(): array
    {
        return array_map(fn (int $v) => self::percentual($v, $this->total), $this->votosPorChapa);
    }

    /** @return array<int|string, float> % de cada chapa sobre os válidos. */
    public function percentuaisDosValidos(): array
    {
        return array_map(fn (int $v) => self::percentual($v, $this->validos), $this->votosPorChapa);
    }

    /** Eleitores que votaram sobre os aptos, em %. */
    public function participacao(): float
    {
        return self::percentual($this->votaram, $this->aptos);
    }

    /** Total de votos igual ao comparecimento? */
    public function conferida(): bool
    {
        return $this->total === $this->votaram;
    }

    /** Parte sobre o todo, em % com 1 casa. Todo zero dá 0. */
    public static function percentual(int $parte, int $todo): float
    {
        return $todo > 0 ? round($parte * 100 / $todo, 1) : 0.0;
    }

    /**
     * @param  array<int|string, int>  $votosPorChapa  ex.: [1 => 40, 2 => 35, 3 => 25]
     * @return array<int|string, float> percentual de cada chapa (0–100, 1 casa decimal)
     */
    public static function percentuais(array $votosPorChapa): array
    {
        $total = array_sum($votosPorChapa);

        return array_map(fn (int $v) => self::percentual($v, $total), $votosPorChapa);
    }
}
