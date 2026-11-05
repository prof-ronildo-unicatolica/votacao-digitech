<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\Voto;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'banco' => DB::connection()->getDriverName().' — '.DB::connection()->getDatabaseName(),
            'contagens' => [
                'eleicoes' => Eleicao::count(),
                'cargos' => Cargo::count(),
                'chapas' => Chapa::count(),
                'candidatos' => Candidato::count(),
                'eleitores' => Eleitor::count(),
                'terminais' => Terminal::count(),
                'sessoes_votacao' => SessaoVotacao::count(),
                'votos' => Voto::count(),
            ],
            'eleicoes' => Eleicao::withCount('cargos')->with(['chapas' => fn ($q) => $q->withCount('candidatos')])->get(),
            // A URL da urna e da API usa o UUID do terminal (D11), não o número.
            'terminal' => Terminal::orderBy('numero')->first(),
        ]);
    }
}
