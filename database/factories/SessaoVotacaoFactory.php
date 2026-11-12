<?php

namespace Database\Factories;

use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SessaoVotacao> */
class SessaoVotacaoFactory extends Factory
{
    protected $model = SessaoVotacao::class;

    public function definition(): array
    {
        return [
            'eleicao_id' => Eleicao::factory(),
            'eleitor_id' => Eleitor::factory(),
            'terminal_id' => Terminal::factory(),
            'mesario_id' => User::factory(),
            'situacao' => 'aberta',
            'liberada_em' => now(),
            'expira_em' => now()->addMinutes(SessaoVotacao::VALIDADE_MINUTOS),
        ];
    }

    public function votou(): static
    {
        return $this->state(fn () => ['situacao' => 'votou', 'votou_em' => now()]);
    }

    public function expirada(): static
    {
        return $this->state(fn () => ['liberada_em' => now()->subHour(), 'expira_em' => now()->subMinutes(30)]);
    }

    public function cancelada(): static
    {
        return $this->state(fn () => ['situacao' => 'cancelada', 'cancelada_em' => now()]);
    }
}
