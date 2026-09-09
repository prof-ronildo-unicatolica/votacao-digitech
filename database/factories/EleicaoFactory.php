<?php

namespace Database\Factories;

use App\Models\Eleicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Eleicao> */
class EleicaoFactory extends Factory
{
    protected $model = Eleicao::class;

    public function definition(): array
    {
        return [
            'titulo' => 'Eleição '.fake()->year(),
            'inicio' => now()->subDay(),
            'fim' => now()->addDays(7),
            'ativa' => true,
        ];
    }

    public function encerrada(): static
    {
        return $this->state(fn () => ['inicio' => now()->subDays(10), 'fim' => now()->subDay()]);
    }
}
