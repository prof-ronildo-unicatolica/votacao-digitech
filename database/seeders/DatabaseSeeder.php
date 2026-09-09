<?php

namespace Database\Seeders;

use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dados mínimos de desenvolvimento (só chaves). NÃO rodar em produção.
 * Quando a equipe adicionar atributos, complete aqui.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'mesario@digitech.local'],
            ['name' => 'Mesário de Teste', 'password' => 'mesario123'],
        );

        $eleicao = Eleicao::first() ?? Eleicao::create();

        foreach ([1, 2, 3] as $numero) {
            Chapa::firstOrCreate(['eleicao_id' => $eleicao->id, 'numero' => $numero]);
        }

        foreach ([1, 2, 3, 4] as $numero) {
            Terminal::firstOrCreate(['numero' => $numero]);
        }

        foreach (['2024007', '2024008', '2024009', '2024010', '2024011', '2024012'] as $matricula) {
            Eleitor::firstOrCreate(['matricula' => $matricula]);
        }
    }
}
