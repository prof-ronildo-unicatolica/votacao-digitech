<?php

namespace Database\Factories;

use App\Models\Eleicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Eleicao> */
class EleicaoFactory extends Factory
{
    protected $model = Eleicao::class;

    /** Padrão: inscrições daqui a 1 semana (2 semanas), votação logo depois (2 dias). */
    public function definition(): array
    {
        return $this->periodos(now()->addWeek()->startOfHour()) + [
            'titulo' => 'Eleição '.fake()->unique()->numerify('Colegiado ####'),
        ];
    }

    /** Hoje está no período de inscrição. */
    public function inscricoesAbertas(): static
    {
        return $this->state(fn () => $this->periodos(now()->subDay()->startOfHour()));
    }

    /** Hoje está no período de votação. */
    public function votacaoAberta(): static
    {
        return $this->state(fn () => [
            'inscricao_abre_em' => now()->subWeeks(3),
            'inscricao_fecha_em' => now()->subWeek(),
            'votacao_abre_em' => now()->subHour(),
            'votacao_fecha_em' => now()->addHours(8),
        ]);
    }

    public function ativa(): static
    {
        return $this->state(fn () => ['ativa' => true]);
    }

    /** As quatro datas a partir da abertura das inscrições, sempre na ordem válida. */
    private function periodos($abre): array
    {
        return [
            'inscricao_abre_em' => $abre,
            'inscricao_fecha_em' => $abre->copy()->addWeeks(2),
            'votacao_abre_em' => $abre->copy()->addWeeks(2)->addDays(3),
            'votacao_fecha_em' => $abre->copy()->addWeeks(2)->addDays(5),
        ];
    }
}
