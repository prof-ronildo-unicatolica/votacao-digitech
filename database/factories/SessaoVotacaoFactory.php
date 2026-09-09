<?php

namespace Database\Factories;

use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SessaoVotacao> */
class SessaoVotacaoFactory extends Factory
{
    protected $model = SessaoVotacao::class;

    public function definition(): array
    {
        return [
            'eleicao_id' => Eleicao::factory(),
            'eleitor_id' => Eleitor::factory(),
            'terminal_id' => Terminal::factory(),
            'mesario_id' => User::factory(),
        ];
    }
}
