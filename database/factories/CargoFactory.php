<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\Eleicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cargo> */
class CargoFactory extends Factory
{
    protected $model = Cargo::class;

    public function definition(): array
    {
        return [
            'eleicao_id' => Eleicao::factory(),
            'ordem' => fake()->unique()->numberBetween(1, 99),
        ];
    }
}
