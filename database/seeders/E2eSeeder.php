<?php

namespace Database\Seeders;

use App\Enums\Perfil;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Teste de ponta a ponta (e2e/, D28): só o admin. Todo o resto (eleição, cargos, eleitores,
 * terminais, mesário, chapas) o próprio teste cadastra pelas telas, como a comissão faria.
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('e2e')) {
            $this->command?->error('Este seed é só do teste de ponta a ponta (APP_ENV=e2e).');

            return;
        }

        User::create(['name' => 'Comissão Eleitoral', 'email' => 'admin@e2e.local', 'password' => 'admin-e2e-123', 'perfil' => Perfil::Admin, 'ativo' => true]);
    }
}
