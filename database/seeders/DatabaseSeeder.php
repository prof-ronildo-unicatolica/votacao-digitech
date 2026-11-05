<?php

namespace Database\Seeders;

use App\Models\Candidato;
use App\Models\Cargo;
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

        // 5 cargos por chapa (ex.: Presidente, Vice, 1º Secretário, 2º Secretário, Tesoureiro).
        // O título é atributo da equipe (H13); aqui só a ordem.
        $cargos = collect([1, 2, 3, 4, 5])->map(
            fn (int $ordem) => Cargo::firstOrCreate(['eleicao_id' => $eleicao->id, 'ordem' => $ordem]),
        );

        // 3 chapas completas: um aluno diferente por cargo (matrículas 2024101 a 2024115).
        foreach ([1, 2, 3] as $numero) {
            $chapa = Chapa::firstOrCreate(['eleicao_id' => $eleicao->id, 'numero' => $numero]);

            foreach ($cargos as $cargo) {
                $matricula = (string) (2024100 + ($numero - 1) * $cargos->count() + $cargo->ordem);
                $aluno = Eleitor::firstOrCreate(['matricula' => $matricula]);

                Candidato::firstOrCreate(
                    ['chapa_id' => $chapa->id, 'cargo_id' => $cargo->id],
                    ['eleicao_id' => $eleicao->id, 'eleitor_id' => $aluno->id],
                );
            }
        }

        foreach ([1, 2, 3, 4] as $numero) {
            Terminal::firstOrCreate(['numero' => $numero]);
        }

        foreach (['2024007', '2024008', '2024009', '2024010', '2024011', '2024012'] as $matricula) {
            Eleitor::firstOrCreate(['matricula' => $matricula]);
        }
    }
}
