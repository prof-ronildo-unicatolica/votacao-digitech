<?php

namespace Tests\Feature;

use App\Enums\AcaoAuditoria;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\EventoAuditoria;
use App\Models\User;
use App\Support\Auditoria;
use App\Support\CorrenteDeHashes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/** H11 fase 4 · Corrente de hashes: adulterar, apagar ou inserir por fora é detectado (D27). */
class CorrenteDeHashesTest extends TestCase
{
    use RefreshDatabase;

    private Eleicao $eleicao;

    /** @var list<EventoAuditoria> */
    private array $eventos = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->eleicao = Eleicao::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->eventos[] = Auditoria::registrar(AcaoAuditoria::Login, usuario: $admin);
        $this->eventos[] = Auditoria::registrar(AcaoAuditoria::EleicaoAlterada, $this->eleicao, ['titulo' => ['antes' => 'Á', 'depois' => 'B']], usuario: $admin);
        $this->eventos[] = Auditoria::registrar(AcaoAuditoria::Logout, usuario: $admin);
    }

    private function adulterar(EventoAuditoria $evento, array $campos): void
    {
        DB::table('auditoria')->where('id', $evento->id)->update($campos);
    }

    public function test_cada_evento_entra_na_corrente_em_ordem(): void
    {
        $anterior = null;
        foreach ($this->eventos as $i => $evento) {
            $relido = EventoAuditoria::find($evento->id);
            $this->assertSame($i + 1, (int) $relido->sequencia);
            $this->assertSame(CorrenteDeHashes::hash($anterior, $relido), $relido->hash, 'O hash relido do banco confere com o calculado.');
            $anterior = $relido->hash;
        }

        $cabeca = CorrenteDeHashes::cabeca();
        $this->assertSame(3, (int) $cabeca->ultima_sequencia);
        $this->assertSame($anterior, $cabeca->ultimo_hash);
        $this->assertTrue(CorrenteDeHashes::verificar()['ok']);
        $this->artisan('digitech:auditoria:verificar')->expectsOutputToContain('A auditoria confere: 3 eventos')->assertSuccessful();
    }

    public function test_alterar_um_evento_por_fora_e_detectado(): void
    {
        $this->adulterar($this->eventos[1], ['dados' => json_encode(['titulo' => ['antes' => 'Á', 'depois' => 'C']])]);

        $r = CorrenteDeHashes::verificar();
        $this->assertFalse($r['ok']);
        $this->assertSame($this->eventos[1]->id, $r['evento']);
        $this->assertStringContainsString('posição 2 não confere', $r['problema']);
        $this->artisan('digitech:auditoria:verificar')->expectsOutputToContain('NÃO confere')->assertFailed();
    }

    public function test_trocar_o_ator_tambem_e_detectado(): void
    {
        $this->adulterar($this->eventos[0], ['ator' => 'Outra pessoa (admin)']);
        $this->assertSame($this->eventos[0]->id, CorrenteDeHashes::verificar()['evento']);
    }

    public function test_apagar_um_evento_do_meio_e_detectado(): void
    {
        DB::table('auditoria')->where('id', $this->eventos[1]->id)->delete();

        $r = CorrenteDeHashes::verificar();
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Falta o evento de posição 2', $r['problema']);
    }

    public function test_apagar_o_ultimo_evento_e_detectado_pela_cabeca(): void
    {
        DB::table('auditoria')->where('id', $this->eventos[2]->id)->delete();

        $this->assertStringContainsString('eventos do fim foram apagados', CorrenteDeHashes::verificar()['problema']);
    }

    public function test_inserir_um_evento_por_fora_e_detectado(): void
    {
        DB::table('auditoria')->insert([
            'id' => '01900000-0000-7000-8000-000000000000', 'ocorrido_em' => now()->addMinute()->format('Y-m-d H:i:s.u'),
            'acao' => 'acesso.login', 'ator' => 'Intruso', 'dados' => '[]',
        ]);

        $this->assertStringContainsString('inserido por fora', CorrenteDeHashes::verificar()['problema']);
    }

    public function test_ordem_das_chaves_no_json_nao_importa(): void
    {
        // O MySQL reordena as chaves de colunas JSON: a corrente não pode depender da ordem.
        $evento = Auditoria::registrar(AcaoAuditoria::TerminalCriado, dados: ['numero' => 3, 'local' => 'Lab']);
        $this->adulterar($evento, ['dados' => '{"local":"Lab","numero":3}']);

        $this->assertTrue(CorrenteDeHashes::verificar()['ok']);
    }

    public function test_acao_desfeita_nao_deixa_buraco_na_corrente(): void
    {
        try {
            DB::transaction(function () {
                Auditoria::registrar(AcaoAuditoria::Logout);
                throw new RuntimeException('a ação falhou');
            });
        } catch (RuntimeException) {
        }
        Auditoria::registrar(AcaoAuditoria::Login);

        $this->assertSame([1, 2, 3, 4], EventoAuditoria::orderBy('sequencia')->pluck('sequencia')->map(fn ($s) => (int) $s)->all());
        $this->assertTrue(CorrenteDeHashes::verificar()['ok']);
    }

    public function test_excluir_usuario_ou_eleicao_nao_quebra_a_corrente(): void
    {
        // O banco anula user_id e eleicao_id (nullOnDelete): isso não é adulteração.
        User::query()->delete();
        $this->eleicao->delete();

        $this->assertNull(EventoAuditoria::find($this->eventos[1]->id)->eleicao_id);
        $this->assertTrue(CorrenteDeHashes::verificar()['ok']);
    }

    public function test_eventos_anteriores_a_corrente_sao_aceitos(): void
    {
        // Gravados antes da fase 4: sem posição, e mais antigos que o primeiro elo.
        DB::table('auditoria')->insert([
            'id' => '01800000-0000-7000-8000-000000000000', 'ocorrido_em' => now()->subYear()->format('Y-m-d H:i:s.u'),
            'acao' => 'acesso.login', 'ator' => 'Antigo', 'dados' => '[]',
        ]);

        $this->assertTrue(CorrenteDeHashes::verificar()['ok']);
    }

    public function test_ata_imprime_o_ultimo_hash(): void
    {
        $eleicao = Eleicao::factory()->create([
            'inscricao_abre_em' => now()->subWeeks(3), 'inscricao_fecha_em' => now()->subWeeks(2),
            'votacao_abre_em' => now()->subDays(2), 'votacao_fecha_em' => now()->subDay(),
        ]);
        Chapa::factory()->for($eleicao)->homologada()->create();
        $cabeca = CorrenteDeHashes::cabeca();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.resultado.ata', $eleicao))
            ->assertOk()->assertSee($cabeca->ultimo_hash)->assertSee("{$cabeca->ultima_sequencia} eventos na corrente");

        $this->assertTrue(CorrenteDeHashes::verificar()['ok'], 'A exportação da ata também entra na corrente.');
    }
}
