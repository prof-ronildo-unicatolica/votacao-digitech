<?php

namespace App\Http\Controllers;

use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\Terminal;
use App\Models\Voto;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $eleicao = Eleicao::ativa();

        return view('home', [
            'eleicao' => $eleicao,
            'chapas' => $eleicao?->chapas ?? collect(),
            'resumo' => [
                'Banco' => DB::connection()->getDriverName().' — '.DB::connection()->getDatabaseName(),
                'Eleitores aptos' => Eleitor::count(),
                'Terminais' => Terminal::count(),
                'Votos registrados' => $eleicao ? Voto::where('eleicao_id', $eleicao->id)->count() : 0,
            ],
        ]);
    }
}
