<?php

namespace Tests\Feature;

use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\Voto;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regras que o BANCO garante (constraints), testadas contra o schema real.
 * Se uma migration nova quebrar uma delas, este arquivo avisa no CI.
 */
class SigiloDoVotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabela_de_votos_nao_tem_nenhuma_ligacao_com_o_eleitor(): void
    {
        $colunas = Schema::getColumnListing('votos');

        foreach (['eleitor_id', 'terminal_id', 'sessao_id', 'sessao_votacao_id', 'mesario_id', 'user_id'] as $proibida) {
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

    public function test_mesmo_eleitor_pode_votar_em_eleicoes_diferentes(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $outra = SessaoVotacao::factory()->create(['eleitor_id' => $sessao->eleitor_id]);

        $this->assertNotSame($sessao->eleicao_id, $outra->eleicao_id);
        $this->assertDatabaseCount('sessoes_votacao', 2);
    }

    public function test_numero_de_chapa_e_unico_por_eleicao(): void
    {
        $chapa = Chapa::factory()->create(['numero' => 5]);

        Chapa::factory()->create(['numero' => 5]); // outra eleição: permitido

        $this->expectException(QueryException::class);
        Chapa::factory()->for($chapa->eleicao)->create(['numero' => 5]);
    }

    public function test_voto_em_branco_e_permitido_sem_chapa(): void
    {
        $eleicao = Eleicao::factory()->create();

        $voto = Voto::create(['eleicao_id' => $eleicao->id, 'chapa_id' => null]);

        $this->assertNull($voto->fresh()->chapa_id);
    }

    public function test_apagar_eleicao_apaga_chapas_sessoes_e_votos_em_cascata(): void
    {
        $voto = Voto::factory()->create();
        SessaoVotacao::factory()->create(['eleicao_id' => $voto->eleicao_id]);

        $voto->eleicao->delete();

        $this->assertDatabaseCount('chapas', 0);
        $this->assertDatabaseCount('sessoes_votacao', 0);
        $this->assertDatabaseCount('votos', 0);
    }

    public function test_terminal_com_sessao_nao_pode_ser_apagado(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->expectException(QueryException::class);
        Terminal::findOrFail($sessao->terminal_id)->delete();
    }
}
