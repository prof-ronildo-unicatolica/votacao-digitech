<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eleicao;
use Illuminate\View\View;

/** Página inicial do admin: a eleição ativa e a fase em que ela está. */
class PainelController extends Controller
{
    public function __invoke(): View
    {
        $eleicao = Eleicao::atual()?->loadCount(['cargos', 'chapas']);

        return view('admin.painel', ['eleicao' => $eleicao]);
    }
}
