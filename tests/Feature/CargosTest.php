<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** H13 · Admin define os cargos que toda chapa da eleição precisa preencher. */
class CargosTest extends TestCase
{
    use RefreshDatabase;

    private Eleicao $eleicao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        $this->eleicao = Eleicao::factory()->create(['titulo' => 'Eleição 2026.2']);
    }

    private function url(string $resto = ''): string
    {
        return "/admin/eleicoes/{$this->eleicao->id}/cargos{$resto}";
    }

    /** @return list<string> títulos na ordem da cédula */
    private function titulos(?Eleicao $eleicao = null): array
    {
        return ($eleicao ?? $this->eleicao)->cargos()->pluck('titulo')->all();
    }

    private function criar(string ...$titulos): void
    {
        foreach ($titulos as $titulo) {
            $this->post($this->url(), ['titulo' => $titulo])->assertRedirect($this->url());
        }
    }

    public function test_admin_adiciona_cargos_no_fim_da_lista(): void
    {
        $this->criar('Presidente', '  Vice-presidente ', 'Tesoureiro');

        $this->assertSame(['Presidente', 'Vice-presidente', 'Tesoureiro'], $this->titulos());
        $this->assertSame([1, 2, 3], $this->eleicao->cargos()->pluck('ordem')->all());
        $this->get($this->url())->assertOk()->assertSee('Cada chapa terá')->assertSee('3 candidatos');
    }

    public function test_titulo_vazio_ou_repetido_na_mesma_eleicao_e_rejeitado(): void
    {
        $this->criar('Presidente');

        $this->post($this->url(), ['titulo' => ''])->assertSessionHasErrors('titulo');
        $this->post($this->url(), ['titulo' => 'presidente'])->assertSessionHasErrors(['titulo' => 'Esta eleição já tem um cargo com este título.']);
        $this->assertSame(['Presidente'], $this->titulos());

        // Em outra eleição o mesmo título é permitido.
        $outra = Eleicao::factory()->create();
        $this->post("/admin/eleicoes/{$outra->id}/cargos", ['titulo' => 'Presidente'])->assertSessionHasNoErrors();
    }

    public function test_renomear_valida_o_titulo_sem_conflitar_consigo_mesmo(): void
    {
        $this->criar('Presidente', 'Vice');
        $vice = $this->eleicao->cargos()->where('ordem', 2)->first();

        $this->put($this->url("/{$vice->id}"), ["titulo_{$vice->id}" => 'Vice-presidente'])->assertSessionHasNoErrors();
        $this->put($this->url("/{$vice->id}"), ["titulo_{$vice->id}" => 'Vice-presidente'])->assertSessionHasNoErrors();
        $this->put($this->url("/{$vice->id}"), ["titulo_{$vice->id}" => 'PRESIDENTE'])->assertSessionHasErrors("titulo_{$vice->id}");

        $this->assertSame(['Presidente', 'Vice-presidente'], $this->titulos());
    }

    public function test_reordenar_troca_com_o_vizinho_e_ignora_as_pontas(): void
    {
        $this->criar('Presidente', 'Tesoureiro', 'Vice-presidente');
        $vice = $this->eleicao->cargos()->where('titulo', 'Vice-presidente')->first();

        $this->post($this->url("/{$vice->id}/mover"), ['direcao' => 'cima'])->assertRedirect($this->url());
        $this->assertSame(['Presidente', 'Vice-presidente', 'Tesoureiro'], $this->titulos());

        $presidente = $this->eleicao->cargos()->where('titulo', 'Presidente')->first();
        $this->post($this->url("/{$presidente->id}/mover"), ['direcao' => 'cima']);
        $this->assertSame(['Presidente', 'Vice-presidente', 'Tesoureiro'], $this->titulos());
        $this->assertSame([1, 2, 3], $this->eleicao->cargos()->pluck('ordem')->all());
    }

    public function test_remover_fecha_o_buraco_na_ordem(): void
    {
        $this->criar('Presidente', 'Vice-presidente', '1º Secretário', 'Tesoureiro');
        $vice = $this->eleicao->cargos()->where('titulo', 'Vice-presidente')->first();

        $this->delete($this->url("/{$vice->id}"))->assertRedirect($this->url());

        $this->assertSame(['Presidente', '1º Secretário', 'Tesoureiro'], $this->titulos());
        $this->assertSame([1, 2, 3], $this->eleicao->cargos()->pluck('ordem')->all());
    }

    public function test_copia_os_cargos_de_outra_eleicao_na_mesma_ordem(): void
    {
        $anterior = Eleicao::factory()->create(['titulo' => 'Eleição 2026.1']);
        foreach (['Presidente', 'Vice-presidente', 'Tesoureiro'] as $ordem => $titulo) {
            Cargo::factory()->for($anterior)->create(['titulo' => $titulo, 'ordem' => $ordem + 1]);
        }

        $this->post($this->url('/copiar'), ['origem_id' => $anterior->id])
            ->assertRedirect($this->url())
            ->assertSessionHas('status', '3 cargos copiados de "Eleição 2026.1".');

        $this->assertSame(['Presidente', 'Vice-presidente', 'Tesoureiro'], $this->titulos());
        $this->assertSame(3, $anterior->cargos()->count(), 'A origem continua com os seus cargos.');

        // Com a lista já preenchida, copiar de novo é recusado (não mistura listas).
        $this->post($this->url('/copiar'), ['origem_id' => $anterior->id])->assertSessionHasErrors('geral');
        $this->assertSame(3, $this->eleicao->cargos()->count());
    }

    public function test_cargos_ficam_bloqueados_depois_da_primeira_chapa_inscrita(): void
    {
        $this->criar('Presidente', 'Vice-presidente');
        $cargo = $this->eleicao->cargos()->first();
        Chapa::factory()->for($this->eleicao)->create();

        $this->post($this->url(), ['titulo' => 'Tesoureiro'])->assertSessionHasErrors('geral');
        $this->put($this->url("/{$cargo->id}"), ["titulo_{$cargo->id}" => 'Outro'])->assertSessionHasErrors('geral');
        $this->post($this->url("/{$cargo->id}/mover"), ['direcao' => 'baixo'])->assertSessionHasErrors('geral');
        $this->delete($this->url("/{$cargo->id}"))->assertSessionHasErrors('geral');

        $this->assertSame(['Presidente', 'Vice-presidente'], $this->titulos());
        $this->get($this->url())->assertOk()->assertSee('Cargos bloqueados')->assertDontSee('Adicionar cargo');
    }

    public function test_cargo_de_outra_eleicao_na_url_da_404(): void
    {
        $deOutra = Cargo::factory()->create();

        $this->delete($this->url("/{$deOutra->id}"))->assertNotFound();
        $this->assertModelExists($deOutra);
    }

    public function test_menu_abre_os_cargos_da_eleicao_ativa_e_avisa_sem_cargos(): void
    {
        $this->get('/admin/cargos')->assertRedirect('/admin/eleicoes');

        $this->eleicao->ativar();
        $this->get('/admin/cargos')->assertRedirect($this->url());

        $this->get('/admin')->assertOk()->assertSee('Sem cargos definidos')->assertSee($this->url(), false);
        $this->get('/admin/eleicoes')->assertOk()->assertSee('Sem cargos: inscrições não abrem');
    }

    public function test_mesario_nao_acessa_os_cargos(): void
    {
        $this->actingAs(User::factory()->create())->get($this->url())->assertForbidden();
    }
}
