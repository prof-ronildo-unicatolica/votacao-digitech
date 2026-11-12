<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eleicao;
use App\Models\Eleitor;
use Illuminate\View\View;

/** Página inicial do admin: a eleição ativa e a fase em que ela está. */
class PainelController extends Controller
{
    public function __invoke(): View
    {
        $eleicao = Eleicao::atual()?->loadCount(['cargos', 'chapas', 'chapas as homologadas_count' => fn ($q) => $q->homologadas(), 'chapas as pendentes_count' => fn ($q) => $q->where('situacao', 'pendente')]);

        return view('admin.painel', ['eleicao' => $eleicao, 'eleitores' => Eleitor::count()]);
    }
}
