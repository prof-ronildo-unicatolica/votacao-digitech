<?php

namespace Tests\Unit;

use App\Support\Apuracao;
use PHPUnit\Framework\TestCase;

/**
 * H10 · Cálculos da apuração. TESTE UNITÁRIO: classe isolada, sem banco, sem HTTP, sem Laravel.
 * Estende PHPUnit\Framework\TestCase (não Tests\TestCase) de propósito.
 */
class ApuracaoTest extends TestCase
{
    public function test_percentuais_respeitam_a_proporcao_dos_votos(): void
    {
        $this->assertSame([1 => 40.0, 2 => 35.0, 3 => 25.0], Apuracao::percentuais([1 => 40, 2 => 35, 3 => 25]));
    }

    public function test_sem_votos_nao_divide_por_zero(): void
    {
        $this->assertSame([1 => 0.0, 2 => 0.0], Apuracao::percentuais([1 => 0, 2 => 0]));
    }

    public function test_vencedora_e_a_chapa_com_mais_votos_validos(): void
    {
        $a = new Apuracao(['a' => 40, 'b' => 35, 'c' => 25], brancos: 6, nulos: 4, votaram: 110, aptos: 200);

        $this->assertSame('a', $a->vencedora());
        $this->assertFalse($a->empate());
        $this->assertSame(100, $a->validos);
        $this->assertSame(110, $a->total);
    }

    public function test_brancos_e_nulos_contam_no_total_mas_nao_nos_validos(): void
    {
        $a = new Apuracao(['a' => 30, 'b' => 10], brancos: 50, nulos: 10, votaram: 100, aptos: 100);

        // Muitos brancos não elegem ninguém: a vencedora sai só dos votos em chapa.
        $this->assertSame('a', $a->vencedora());
        $this->assertSame(['a' => 30.0, 'b' => 10.0], $a->percentuaisDoTotal());
        $this->assertSame(['a' => 75.0, 'b' => 25.0], $a->percentuaisDosValidos());
    }

    public function test_empate_no_primeiro_lugar_nao_tem_vencedora(): void
    {
        $a = new Apuracao(['a' => 20, 'b' => 20, 'c' => 5], brancos: 0, nulos: 0, votaram: 45, aptos: 60);

        $this->assertNull($a->vencedora());
        $this->assertTrue($a->empate());
        $this->assertSame(['a', 'b'], $a->primeiras);
    }

    public function test_empate_abaixo_do_primeiro_lugar_nao_importa(): void
    {
        $a = new Apuracao(['a' => 30, 'b' => 10, 'c' => 10], brancos: 0, nulos: 0, votaram: 50, aptos: 60);

        $this->assertSame('a', $a->vencedora());
        $this->assertFalse($a->empate());
    }

    public function test_sem_votos_validos_nao_tem_vencedora(): void
    {
        $a = new Apuracao(['a' => 0, 'b' => 0], brancos: 3, nulos: 2, votaram: 5, aptos: 10);

        $this->assertNull($a->vencedora());
        $this->assertFalse($a->empate());
        $this->assertSame(['a' => 0.0, 'b' => 0.0], $a->percentuaisDosValidos());
    }

    public function test_eleicao_sem_nenhum_voto_nao_divide_por_zero(): void
    {
        $a = new Apuracao(['a' => 0], brancos: 0, nulos: 0, votaram: 0, aptos: 0);

        $this->assertNull($a->vencedora());
        $this->assertSame(0.0, $a->participacao());
        $this->assertSame(['a' => 0.0], $a->percentuaisDoTotal());
        $this->assertTrue($a->conferida());
    }

    public function test_participacao_e_votaram_sobre_aptos(): void
    {
        $this->assertSame(33.3, (new Apuracao(['a' => 1], 0, 0, votaram: 1, aptos: 3))->participacao());
    }

    public function test_conferencia_acusa_total_diferente_do_comparecimento(): void
    {
        $this->assertTrue((new Apuracao(['a' => 3], 1, 1, votaram: 5, aptos: 9))->conferida());
        $this->assertFalse((new Apuracao(['a' => 3], 1, 1, votaram: 4, aptos: 9))->conferida());
    }
}
