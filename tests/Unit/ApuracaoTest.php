<?php

namespace Tests\Unit;

use App\Support\Apuracao;
use PHPUnit\Framework\TestCase;

/**
 * EXEMPLO de TESTE UNITÁRIO: testa uma classe isolada, sem banco, sem HTTP, sem Laravel.
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
}
