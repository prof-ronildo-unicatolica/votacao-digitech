<?php

namespace Tests\Feature;

use App\Models\Chapa;
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
class ConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sigilo_do_voto_tabela_votos_nao_liga_ao_eleitor(): void
    {
        $colunas = Schema::getColumnListing('votos');

        foreach (['eleitor_id', 'terminal_id', 'sessao_id', 'sessao_votacao_id', 'mesario_id', 'user_id'] as $proibida) {
            $this->assertNotContains($proibida, $colunas, "votos.$proibida quebraria o sigilo do voto");
        }
    }

    public function test_eleitor_tem_uma_unica_sessao_por_eleicao(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->expectException(QueryException::class);

        SessaoVotacao::factory()->create([
            'eleicao_id' => $sessao->eleicao_id,
            'eleitor_id' => $sessao->eleitor_id,
        ]);
    }

    public function test_numero_de_chapa_e_unico_por_eleicao(): void
    {
        $chapa = Chapa::factory()->create(['numero' => 5]);

        Chapa::factory()->create(['numero' => 5]); // outra eleição: permitido

        $this->expectException(QueryException::class);
        Chapa::factory()->for($chapa->eleicao)->create(['numero' => 5]);
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
