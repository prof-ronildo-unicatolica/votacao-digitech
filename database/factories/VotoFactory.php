<?php

namespace Database\Factories;

use App\Models\Chapa;
use App\Models\Voto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Voto> */
class VotoFactory extends Factory
{
    protected $model = Voto::class;

    public function definition(): array
    {
        $chapa = Chapa::factory()->create();

        return [
            'eleicao_id' => $chapa->eleicao_id,
            'chapa_id' => $chapa->id,
        ];
    }
}
