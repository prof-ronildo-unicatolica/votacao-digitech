<?php

namespace Tests\Feature;

use App\Models\SessaoVotacao;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regras de negócio que o BANCO precisa garantir, testadas contra o schema real.
 */
class SigiloDoVotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabela_de_votos_nao_tem_nenhuma_ligacao_com_o_eleitor(): void
    {
        $colunas = Schema::getColumnListing('votos');

        foreach (['eleitor_id', 'terminal_id', 'sessao_id', 'sessao_votacao_id', 'user_id'] as $proibida) {
            $this->assertNotContains($proibida, $colunas, "votos.$proibida quebraria o sigilo do voto");
        }
    }

    public function test_eleitor_nao_pode_ter_duas_sessoes_na_mesma_eleicao(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->expectException(QueryException::class);

        SessaoVotacao::factory()->create([
            'eleicao_id' => $sessao->eleicao_id,
            'eleitor_id' => $sessao->eleitor_id,
        ]);
    }
}
