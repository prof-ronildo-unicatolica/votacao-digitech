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
        ];
    }
}
