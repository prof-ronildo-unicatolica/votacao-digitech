<?php

namespace Database\Factories;

use App\Models\Terminal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Terminal> */
class TerminalFactory extends Factory
{
    protected $model = Terminal::class;

    public function definition(): array
    {
        return [
            'numero' => fake()->unique()->numberBetween(1, 99),
            'nome' => 'Terminal '.fake()->unique()->word(),
        ];
    }
}
