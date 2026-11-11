<?php

namespace Database\Seeders;

use App\Enums\Perfil;
use App\Enums\SituacaoChapa;
use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dados de desenvolvimento. NÃO roda em produção (o próprio seed se recusa).
 * Usuários: admin@digitech.local / admin123 e mesario@digitech.local / mesario123.
 * Quando a equipe adicionar atributos, complete aqui.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Seed é só para desenvolvimento. Em produção, crie o admin com: php artisan digitech:admin');

            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@digitech.local'],
            ['name' => 'Admin de Teste', 'password' => 'admin123', 'perfil' => Perfil::Admin, 'ativo' => true],
        );
        User::updateOrCreate(
            ['email' => 'mesario@digitech.local'],
            ['name' => 'Mesário de Teste', 'password' => 'mesario123', 'perfil' => Perfil::Mesario, 'ativo' => true],
        );

        // Eleição de desenvolvimento, ativa, com as inscrições abertas desde ontem.
        $abre = now()->subDay()->setTime(8, 0);
        $eleicao = Eleicao::first() ?? Eleicao::create(['titulo' => 'Eleição de teste do Colegiado']);
        $eleicao->update([
            'titulo' => $eleicao->titulo ?: 'Eleição de teste do Colegiado',
            'inscricao_abre_em' => $eleicao->inscricao_abre_em ?? $abre,
            'inscricao_fecha_em' => $eleicao->inscricao_fecha_em ?? $abre->copy()->addWeeks(2)->setTime(18, 0),
            'votacao_abre_em' => $eleicao->votacao_abre_em ?? $abre->copy()->addWeeks(2)->addDays(3),
            'votacao_fecha_em' => $eleicao->votacao_fecha_em ?? $abre->copy()->addWeeks(2)->addDays(3)->setTime(18, 0),
        ]);
        if (! Eleicao::atual()) {
            $eleicao->ativar();
        }

        // 5 cargos por chapa, na ordem da cédula (H13).
        $titulos = [1 => 'Presidente', 'Vice-presidente', '1º Secretário', '2º Secretário', 'Tesoureiro'];
        $cargos = collect($titulos)->map(
            fn (string $titulo, int $ordem) => Cargo::updateOrCreate(['eleicao_id' => $eleicao->id, 'ordem' => $ordem], ['titulo' => $titulo]),
        )->values();

        // 3 chapas completas: um aluno diferente por cargo (matrículas 2024101 a 2024115).
        foreach ([1, 2, 3] as $numero) {
            // Chapas 1 e 2 homologadas, 3 pendente (para testar a homologação, H3).
            $chapa = Chapa::firstOrCreate(['eleicao_id' => $eleicao->id, 'numero' => $numero], [
                'nome' => ['', 'Movimento Estudantil', 'Renova DigiTech', 'Voz do Aluno'][$numero],
                'situacao' => $numero < 3 ? SituacaoChapa::Homologada : SituacaoChapa::Pendente,
                'contato_email' => "chapa{$numero}@aluno.digitech.local",
            ]);

            foreach ($cargos as $cargo) {
                $matricula = (string) (2024100 + ($numero - 1) * $cargos->count() + $cargo->ordem);
                $aluno = Eleitor::firstOrCreate(['matricula' => $matricula], ['nome' => fake('pt_BR')->name(), 'turma' => 'ADS 3']);

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
            Eleitor::firstOrCreate(['matricula' => $matricula], ['nome' => fake('pt_BR')->name(), 'turma' => 'SI 2']);
        }
    }
}
