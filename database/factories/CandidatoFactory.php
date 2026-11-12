<?php

namespace Database\Factories;

use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Candidato> */
class CandidatoFactory extends Factory
{
    protected $model = Candidato::class;

    /**
     * Chapa, cargo e candidato são sempre da MESMA eleição (o banco exige: FKs compostas).
     * Uso típico: Candidato::factory()->for($chapa)->for($cargo)->create()
     */
    public function definition(): array
    {
        return [
            'chapa_id' => Chapa::factory(),
            'eleicao_id' => fn (array $atributos) => Chapa::findOrFail($atributos['chapa_id'])->eleicao_id,
            'cargo_id' => fn (array $atributos) => Cargo::factory()->create(['eleicao_id' => $atributos['eleicao_id']])->id,
            'eleitor_id' => Eleitor::factory(),
        ];
    }
}
