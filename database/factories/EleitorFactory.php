<?php

namespace Database\Factories;

use App\Models\Eleitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Eleitor> */
class EleitorFactory extends Factory
{
    protected $model = Eleitor::class;

    public function definition(): array
    {
        return [
            'matricula' => (string) fake()->unique()->numberBetween(2020000, 2029999),
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'turma' => fake()->randomElement(['ADS 2026.1', 'ADS 2026.2', 'ENG 2026.1']),
        ];
    }
}
