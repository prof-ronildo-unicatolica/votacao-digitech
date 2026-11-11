<?php

namespace Tests\Feature;

use App\Enums\AcaoAuditoria;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\EventoAuditoria;
use App\Models\SessaoVotacao;
use App\Models\User;
use App\Models\Voto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** H10 · Resultado: só comparecimento antes do fim; apuração, eleitos e ata depois (D26). */
class ResultadoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Eleicao $eleicao;

    private Chapa $dez;

    private Chapa $vinte;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['perfil' => 'admin']);
        $this->eleicao = Eleicao::factory()->votacaoAberta()->ativa()->create(['titulo' => 'Colegiado 2026.2']);
        $presidente = Cargo::factory()->for($this->eleicao)->create(['titulo' => 'Presidente', 'ordem' => 1]);
        $tesoureiro = Cargo::factory()->for($this->eleicao)->create(['titulo' => 'Tesoureiro', 'ordem' => 2]);

        $this->dez = Chapa::factory()->for($this->eleicao)->homologada()->create(['numero' => 10, 'nome' => 'Inovação Acadêmica']);
        $this->vinte = Chapa::factory()->for($this->eleicao)->homologada()->create(['numero' => 20, 'nome' => 'Movimento Estudantil']);
        // Cadastrados ao contrário da ordem dos cargos: os eleitos saem na ordem da cédula.
        $this->candidato($this->vinte, $tesoureiro, 'Bruno Lima', '2024202');
        $this->candidato($this->vinte, $presidente, 'Carla Dias', '2024201');
        $this->candidato($this->dez, $presidente, 'Ana Souza', '2024101');
        $this->candidato($this->dez, $tesoureiro, 'Davi Rocha', '2024102');
    }

    private function candidato(Chapa $chapa, Cargo $cargo, string $nome, string $matricula): void
    {
        $chapa->candidatos()->create(['eleicao_id' => $this->eleicao->id, 'cargo_id' => $cargo->id, 'eleitor_id' => Eleitor::factory()->create(['nome' => $nome, 'matricula' => $matricula])->id]);
    }

    /** Votos como a urna grava (H8): cada voto com uma sessão "votou" de um eleitor diferente. */
    private function votar(?Chapa $chapa, int $quantos, string $tipo = 'chapa'): void
    {
        for ($i = 0; $i < $quantos; $i++) {
            Voto::create(['eleicao_id' => $this->eleicao->id, 'chapa_id' => $chapa?->id, 'tipo' => $tipo]);
            SessaoVotacao::factory()->votou()->for($this->eleicao)->create();
        }
    }

    private function encerrar(): void
    {
        $this->travelTo($this->eleicao->votacao_fecha_em);
    }

    public function test_antes_do_fim_mostra_so_o_comparecimento(): void
    {
        $this->votar($this->dez, 3);
        $this->votar($this->vinte, 1);
        $aptos = Eleitor::count();

        $this->actingAs($this->admin)->get('/admin/resultado')
            ->assertOk()
            ->assertSee('O resultado sai quando a votação encerrar')
            ->assertSee('Comparecimento')
            ->assertSee("de {$aptos} aptos")
            ->assertDontSee('Votos por opção')
            ->assertDontSee('grafico-resultado')
            ->assertDontSee('Exportar CSV')
            ->assertDontSee('Inovação Acadêmica');

        $this->assertSame(0, EventoAuditoria::where('acao', AcaoAuditoria::ResultadoConsultado)->count(), 'Comparecimento não é apuração.');
    }

    public function test_antes_do_fim_csv_e_ata_sao_recusados(): void
    {
        $this->votar($this->dez, 1);
        $voltar = route('admin.resultado', ['eleicao' => $this->eleicao->id]);

        $this->actingAs($this->admin)->get(route('admin.resultado.csv', $this->eleicao))
            ->assertRedirect($voltar)->assertSessionHasErrors('geral');
        $this->get(route('admin.resultado.ata', $this->eleicao))
            ->assertRedirect($voltar)->assertSessionHasErrors('geral');
        $this->get($voltar)->assertSee('A ata só pode ser exportada depois que a votação encerrar, em '.$this->eleicao->votacao_fecha_em->format('d/m/Y'));

        $this->assertSame(0, EventoAuditoria::where('acao', AcaoAuditoria::AtaExportada)->count());
    }

    public function test_um_segundo_antes_do_fim_ainda_esta_bloqueado(): void
    {
        $this->travelTo($this->eleicao->votacao_fecha_em->copy()->subSecond());

        $this->actingAs($this->admin)->get('/admin/resultado')->assertSee('O resultado sai quando a votação encerrar');
    }

    public function test_depois_do_fim_apura_por_chapa_brancos_nulos_e_participacao(): void
    {
        $this->votar($this->dez, 3);
        $this->votar($this->vinte, 5);
        $this->votar(null, 1, 'branco');
        $this->votar(null, 1, 'nulo');
        $aptos = Eleitor::count(); // 4 candidatos + 10 que votaram
        $this->encerrar();

        $resposta = $this->actingAs($this->admin)->get('/admin/resultado')->assertOk();

        $resposta->assertSee('Encerrada em')
            ->assertSee('Votos por opção')
            ->assertSee('grafico-resultado')
            ->assertSee('chart.js@4.4.4')
            // 10 · 3 votos: 30% do total, 37,5% dos válidos. 20 · 5 votos: 50% e 62,5%.
            ->assertSeeInOrder(['10 · Inovação Acadêmica', '3', '30,0%', '37,5%'])
            ->assertSeeInOrder(['20 · Movimento Estudantil', '5', '50,0%', '62,5%'])
            ->assertSeeInOrder(['Brancos', '1', '10,0%', 'Nulos', '1', '10,0%'])
            ->assertSee('8 válidos (em chapa)')
            ->assertSee("10 de {$aptos} aptos")
            ->assertSee(number_format(1000 / $aptos, 1, ',', '.').'%')
            ->assertSee('Chapa vencedora')
            ->assertSee('62,5% dos votos válidos')
            ->assertDontSee('Total de votos diferente do comparecimento');

        $resposta->assertSeeInOrder(['Eleitos · chapa 20 · Movimento Estudantil', 'Presidente', 'Carla Dias', 'Tesoureiro', 'Bruno Lima']);
        $resposta->assertDontSee('Ana Souza');

        $evento = EventoAuditoria::where('acao', AcaoAuditoria::ResultadoConsultado)->sole();
        $this->assertSame($this->eleicao->id, $evento->eleicao_id);
        $this->assertSame($this->admin->id, $evento->user_id);
    }

    public function test_so_chapas_homologadas_entram_na_apuracao(): void
    {
        $indeferida = Chapa::factory()->for($this->eleicao)->indeferida()->create(['numero' => 30, 'nome' => 'Chapa Fora']);
        Chapa::factory()->for($this->eleicao)->create(['numero' => 40, 'nome' => 'Chapa Pendente']);
        $this->votar($this->dez, 2);
        // Um voto numa chapa fora da cédula não deveria existir (H8 recusa). Se existir, não conta
        // para ninguém, e a conferência avisa.
        $this->votar($indeferida, 1);
        $this->encerrar();

        $this->actingAs($this->admin)->get('/admin/resultado')
            ->assertSee('10 · Inovação Acadêmica')
            ->assertSee('20 · Movimento Estudantil')
            ->assertDontSee('Chapa Fora')
            ->assertDontSee('Chapa Pendente')
            ->assertSee('Total de votos diferente do comparecimento')
            ->assertSee('Há 2 votos na apuração e 3 eleitores votaram');
    }

    public function test_empate_no_primeiro_lugar_nao_declara_vencedora(): void
    {
        $this->votar($this->dez, 2);
        $this->votar($this->vinte, 2);
        $this->encerrar();

        $this->actingAs($this->admin)->get('/admin/resultado')
            ->assertSee('Empate no primeiro lugar')
            ->assertSee('10 e 20')
            ->assertSee('Aguardando o desempate')
            ->assertDontSee('Chapa vencedora')
            ->assertDontSee('Carla Dias');
    }

    public function test_eleicao_encerrada_sem_votos(): void
    {
        $this->encerrar();

        $this->actingAs($this->admin)->get('/admin/resultado')->assertOk()
            ->assertSee('Nenhuma chapa eleita')
            ->assertSee('Nenhum voto em chapa.');
    }

    public function test_exporta_a_ata_em_csv_com_os_mesmos_numeros(): void
    {
        $this->votar($this->dez, 1);
        $this->votar($this->vinte, 3);
        $this->encerrar();

        $resposta = $this->actingAs($this->admin)->get(route('admin.resultado.csv', $this->eleicao));

        $resposta->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="ata-colegiado-20262.csv"');
        $csv = $resposta->getContent();
        $this->assertStringStartsWith("\u{FEFF}Eleição;\"Colegiado 2026.2\"", $csv);
        $this->assertStringContainsString("Votaram;4\n", $csv);
        $this->assertStringContainsString("Resultado;\"Chapa 20 · Movimento Estudantil\"\n", $csv);
        $this->assertStringContainsString("Opção;Votos;\"% do total\";\"% dos válidos\"\n", $csv);
        $this->assertStringContainsString("\"10 · Inovação Acadêmica\";1;25,0;25,0\n", $csv);
        $this->assertStringContainsString("\"20 · Movimento Estudantil\";3;75,0;75,0\n", $csv);
        $this->assertStringContainsString("Brancos;0;0,0;\n", $csv);
        $this->assertStringContainsString("Presidente;\"Carla Dias\";2024201\nTesoureiro;\"Bruno Lima\";2024202\n", $csv);

        $evento = EventoAuditoria::where('acao', AcaoAuditoria::AtaExportada)->sole();
        $this->assertSame(['formato' => 'csv', 'sha256' => hash('sha256', $csv)], $evento->dados);
    }

    public function test_ata_para_imprimir_ou_salvar_em_pdf(): void
    {
        $this->votar($this->dez, 4);
        $this->votar($this->vinte, 1);
        $this->encerrar();

        $resposta = $this->actingAs($this->admin)->get(route('admin.resultado.ata', $this->eleicao));

        $resposta->assertOk()
            ->assertSee('Ata de apuração · Colegiado 2026.2')
            ->assertSee('Imprimir ou salvar em PDF')
            ->assertSee('votos apurados iguais ao número de eleitores que votaram')
            ->assertSeeInOrder(['Eleita a', 'chapa 10 · Inovação Acadêmica', 'Presidente', 'Ana Souza', '2024101', 'Tesoureiro', 'Davi Rocha'])
            ->assertSee('Presidente da comissão eleitoral')
            ->assertDontSee('dt-menu-lateral');

        $evento = EventoAuditoria::where('acao', AcaoAuditoria::AtaExportada)->sole();
        $this->assertSame('pdf', $evento->dados['formato']);
        $this->assertSame(hash('sha256', $resposta->getContent()), $evento->dados['sha256']);
    }

    public function test_escolhe_outra_eleicao_pela_lista(): void
    {
        $antiga = Eleicao::factory()->create([
            'titulo' => 'Colegiado 2025.1',
            'inscricao_abre_em' => now()->subYear(),
            'inscricao_fecha_em' => now()->subYear()->addWeek(),
            'votacao_abre_em' => now()->subYear()->addWeeks(2),
            'votacao_fecha_em' => now()->subYear()->addWeeks(2)->addHours(8),
        ]);
        $chapa = Chapa::factory()->for($antiga)->homologada()->create(['numero' => 7, 'nome' => 'Chapa Antiga']);
        Voto::create(['eleicao_id' => $antiga->id, 'chapa_id' => $chapa->id]);

        $this->actingAs($this->admin)->get('/admin/resultado')
            ->assertSee('Colegiado 2026.2 (ativa)', false)
            ->assertSee('O resultado sai quando a votação encerrar');

        $this->get('/admin/resultado?eleicao='.$antiga->id)
            ->assertSee('7 · Chapa Antiga')
            ->assertSee('Exportar CSV');
    }

    public function test_sem_eleicao_cadastrada(): void
    {
        Eleicao::query()->delete();

        $this->actingAs($this->admin)->get('/admin/resultado')->assertOk()->assertSee('Nenhuma eleição cadastrada');
    }

    public function test_so_o_admin_ve_o_resultado(): void
    {
        $this->encerrar();
        $mesario = User::factory()->create(['perfil' => 'mesario']);

        $this->actingAs($mesario)->get('/admin/resultado')->assertForbidden();
        $this->actingAs($mesario)->get(route('admin.resultado.csv', $this->eleicao))->assertForbidden();
        $this->actingAs($mesario)->get(route('admin.resultado.ata', $this->eleicao))->assertForbidden();
        auth()->logout();
        $this->get('/admin/resultado')->assertRedirect('/login');
    }

    public function test_menu_leva_ao_resultado(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertSee(route('admin.resultado'));
    }
}
