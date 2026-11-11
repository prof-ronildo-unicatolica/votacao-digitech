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
use Illuminate\Support\Str;
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

    public function test_sigilo_do_voto_tabela_votos_nao_guarda_horario(): void
    {
        $colunas = Schema::getColumnListing('votos');

        foreach (['created_at', 'updated_at'] as $proibida) {
            $this->assertNotContains($proibida, $colunas, "votos.$proibida permitiria cruzar o horário com sessoes_votacao");
        }
    }

    public function test_sigilo_do_voto_id_e_uuid_v4_aleatorio(): void
    {
        $voto = Voto::factory()->create();

        $this->assertTrue(Str::isUuid($voto->id), 'votos.id deve ser UUID');
        $this->assertSame('4', $voto->id[14], 'votos.id deve ser UUID v4 (aleatório), não ordenado por tempo');
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

    public function test_voto_so_pode_ir_para_chapa_da_mesma_eleicao(): void
    {
        $chapaDeOutraEleicao = Chapa::factory()->create();
        $eleicao = Eleicao::factory()->create();

        $this->expectException(QueryException::class);
        Voto::create(['eleicao_id' => $eleicao->id, 'chapa_id' => $chapaDeOutraEleicao->id]);
    }

    public function test_voto_sem_chapa_e_aceito(): void
    {
        $voto = Voto::factory()->semChapa()->create();

        $this->assertNull($voto->chapa_id);
        $this->assertDatabaseCount('votos', 1);
    }

    public function test_chapa_com_votos_nao_pode_ser_apagada(): void
    {
        $voto = Voto::factory()->create();

        $this->expectException(QueryException::class);
        $voto->chapa->delete();
    }

    public function test_eleicao_com_votos_nao_pode_ser_apagada(): void
    {
        $voto = Voto::factory()->create();

        $this->expectException(QueryException::class);
        $voto->eleicao->delete();
    }

    public function test_apagar_eleicao_sem_votos_apaga_chapas_e_sessoes_em_cascata(): void
    {
        $chapa = Chapa::factory()->create();
        SessaoVotacao::factory()->create(['eleicao_id' => $chapa->eleicao_id]);

        $chapa->eleicao->delete();

        $this->assertDatabaseCount('chapas', 0);
        $this->assertDatabaseCount('sessoes_votacao', 0);
    }

    public function test_eleitor_com_sessao_nao_pode_ser_apagado(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->expectException(QueryException::class);
        $sessao->eleitor->delete();
    }

    public function test_mesario_com_sessao_nao_pode_ser_apagado(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->expectException(QueryException::class);
        $sessao->mesario->delete();
    }

    public function test_sessao_exige_mesario(): void
    {
        $this->expectException(QueryException::class);
        SessaoVotacao::factory()->create(['mesario_id' => null]);
    }

    public function test_terminal_com_sessao_nao_pode_ser_apagado(): void
    {
        $sessao = SessaoVotacao::factory()->create();

        $this->expectException(QueryException::class);
        Terminal::findOrFail($sessao->terminal_id)->delete();
    }
}
