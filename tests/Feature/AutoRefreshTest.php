<?php

namespace Tests\Feature;

use Tests\TestCase;

/** O auto-refresh de desenvolvimento nunca pode existir fora de APP_ENV=local. */
class AutoRefreshTest extends TestCase
{
    public function test_rota_de_auto_refresh_nao_existe_fora_do_ambiente_local(): void
    {
        $this->assertFalse(app()->isLocal());
        $this->get('/_dev/recarregar')->assertNotFound();
        $this->get('/estilo')->assertDontSee('recarregar');
    }
}
