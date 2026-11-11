<?php

namespace Database\Factories;

use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Voto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Voto> */
class VotoFactory extends Factory
{
    protected $model = Voto::class;

    /**
     * A eleição do voto é sempre a da chapa (o banco exige isso: FK composta).
     * Voto com chapa: Voto::factory()->for($chapa)->create()
     */
    public function definition(): array
    {
        return [
            'chapa_id' => Chapa::factory(),
            'eleicao_id' => fn (array $atributos) => $atributos['chapa_id']
                ? Chapa::findOrFail($atributos['chapa_id'])->eleicao_id
                : Eleicao::factory(),
        ];
    }

    /** Voto sem chapa (branco/nulo). Ex.: Voto::factory()->semChapa()->for($eleicao)->create() */
    public function semChapa(): static
    {
        return $this->state(fn () => ['chapa_id' => null]);
    }
}
