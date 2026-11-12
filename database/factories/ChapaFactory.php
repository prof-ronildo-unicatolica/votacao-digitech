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
            'nome' => fn (array $atributos) => "Chapa {$atributos['numero']}",
            'contato_email' => fake()->safeEmail(),
        ];
    }

    public function homologada(): static
    {
        return $this->state(fn () => ['situacao' => 'homologada', 'analisada_em' => now()]);
    }

    public function indeferida(string $motivo = 'Candidato a tesoureiro não está matriculado.'): static
    {
        return $this->state(fn () => ['situacao' => 'indeferida', 'motivo_indeferimento' => $motivo, 'analisada_em' => now()]);
    }
}
