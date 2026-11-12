<?php

namespace App\Http\Controllers;

use App\Models\Eleicao;
use Illuminate\View\View;

/**
 * Página inicial pública: a eleição ativa, em que fase ela está, o que cada pessoa pode fazer
 * agora e as chapas homologadas. Sem login. Não mostra chapa pendente ou indeferida (H14),
 * nem comparecimento ou resultado (esses ficam com a comissão, H10).
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        $eleicao = Eleicao::atual();

        return view('home', [
            'eleicao' => $eleicao,
            'fase' => $eleicao?->fase(),
            'chapas' => $eleicao ? $eleicao->chapasNaUrna()->with('composicao')->get() : collect(),
        ]);
    }
}
