<?php

namespace Database\Seeders;

use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dados de desenvolvimento. NÃO rodar em produção (`migrate --seed` só localmente).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@digitech.local'],
            ['name' => 'Administrador', 'password' => 'admin123', 'perfil' => 'admin'],
        );
        User::updateOrCreate(
            ['email' => 'mesario@digitech.local'],
            ['name' => 'Mesário de Teste', 'password' => 'mesario123', 'perfil' => 'mesario'],
        );

        $eleicao = Eleicao::updateOrCreate(
            ['titulo' => 'Eleição do Colegiado 2026'],
            ['inicio' => now()->subDay(), 'fim' => now()->addDays(30), 'ativa' => true],
        );

        foreach ([
            [1, 'Transformação', 'João Silva (presidente) e Maria Santos (vice)'],
            [2, 'Movimento', 'Carlos Costa (presidente) e Ana Oliveira (vice)'],
            [3, 'Integração', 'Pedro Alves (presidente) e Sofia Martins (vice)'],
        ] as [$numero, $nome, $descricao]) {
            Chapa::updateOrCreate(
                ['eleicao_id' => $eleicao->id, 'numero' => $numero],
                ['nome' => $nome, 'descricao' => $descricao],
            );
        }

        foreach ([
            [1, 'Lab Informática A'],
            [2, 'Lab Informática B'],
            [3, 'Sala 101'],
            [4, 'Biblioteca'],
        ] as [$numero, $nome]) {
            Terminal::updateOrCreate(['numero' => $numero], ['nome' => $nome]);
        }

        foreach ([
            ['2024007', 'Lucas Ferreira', 'ADS 2026.1'],
            ['2024008', 'Beatriz Lima', 'ADS 2026.1'],
            ['2024009', 'Gabriel Rocha', 'ADS 2026.2'],
            ['2024010', 'Camila Souza', 'ADS 2026.2'],
            ['2024011', 'Rafael Torres', 'ENG 2026.1'],
            ['2024012', 'Isabela Gomes', 'ENG 2026.1'],
        ] as [$matricula, $nome, $turma]) {
            Eleitor::updateOrCreate(['matricula' => $matricula], ['nome' => $nome, 'turma' => $turma]);
        }
    }
}
