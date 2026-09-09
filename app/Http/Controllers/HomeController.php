<?php

namespace App\Http\Controllers;

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
                'chapas' => Chapa::count(),
                'eleitores' => Eleitor::count(),
                'terminais' => Terminal::count(),
                'sessoes_votacao' => SessaoVotacao::count(),
                'votos' => Voto::count(),
            ],
            'eleicoes' => Eleicao::with('chapas')->get(),
        ]);
    }
}
