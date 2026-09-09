<?php

namespace Tests\Unit;

use App\Support\Apuracao;
use PHPUnit\Framework\TestCase;

/**
 * TESTE UNITÁRIO: testa uma classe isolada, sem banco, sem HTTP, sem Laravel.
 * Estende PHPUnit\Framework\TestCase (não Tests\TestCase) de propósito.
 */
class ApuracaoTest extends TestCase
{
    public function test_percentuais_somam_cem_e_respeitam_proporcao(): void
    {
        $percentuais = Apuracao::percentuais([1 => 40, 2 => 35, 3 => 25]);

        $this->assertSame([1 => 40.0, 2 => 35.0, 3 => 25.0], $percentuais);
        $this->assertEquals(100.0, array_sum($percentuais));
    }

    public function test_percentuais_sem_votos_nao_divide_por_zero(): void
    {
        $this->assertSame([1 => 0.0, 2 => 0.0], Apuracao::percentuais([1 => 0, 2 => 0]));
    }

    public function test_vencedora_e_a_chapa_com_mais_votos(): void
    {
        $this->assertSame(2, Apuracao::vencedora([1 => 10, 2 => 12, 3 => 3]));
    }

    public function test_empate_nao_tem_vencedora(): void
    {
        $this->assertNull(Apuracao::vencedora([1 => 10, 2 => 10]));
        $this->assertNull(Apuracao::vencedora([]));
    }

    public function test_participacao_e_limitada_a_cem_por_cento(): void
    {
        $this->assertSame(50.0, Apuracao::participacao(50, 100));
        $this->assertSame(100.0, Apuracao::participacao(120, 100));
        $this->assertSame(0.0, Apuracao::participacao(10, 0));
    }
}
