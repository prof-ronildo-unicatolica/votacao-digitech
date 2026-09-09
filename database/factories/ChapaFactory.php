<?php

namespace Database\Factories;

use App\Models\Chapa;
use App\Models\Eleicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Chapa> */
class ChapaFactory extends Factory
{
    protected $model = Chapa::class;

    public function definition(): array
    {
        return [
            'eleicao_id' => Eleicao::factory(),
            'numero' => fake()->unique()->numberBetween(1, 99),
        ];
    }
}
