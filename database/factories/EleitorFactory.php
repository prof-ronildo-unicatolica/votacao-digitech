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
            'turma' => fake()->randomElement(['ADS 1', 'ADS 3', 'ADS 5', 'SI 2', 'SI 4']),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
