<?php

namespace Tests\Feature;

use App\Http\Controllers\EstiloController;
use Tests\TestCase;

/** Vitrine da identidade visual (/estilo): renderiza todos os componentes e telas de referência. */
class EstiloTest extends TestCase
{
    public function test_vitrine_mostra_marca_cores_e_componentes(): void
    {
        $this->get('/estilo')
            ->assertOk()
            ->assertSee('css/tokens.css')
            ->assertSee('laranja.700')
            ->assertSee('#B5480A')
            ->assertSee('dt-terminal', false)
            ->assertSee('dt-opcao', false);
    }

    public function test_todas_as_telas_de_referencia_renderizam(): void
    {
        foreach (array_keys(EstiloController::TELAS) as $tela) {
            $this->get("/estilo/telas/$tela")->assertOk();
        }
    }

    public function test_tela_inexistente_da_404(): void
    {
        $this->get('/estilo/telas/nao-existe')->assertNotFound();
    }
}
