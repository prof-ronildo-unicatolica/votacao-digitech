<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use Illuminate\View\View;

/** Urna de um terminal, aberta em tela cheia no computador de votação: /urna/{uuid do terminal}. */
class UrnaController extends Controller
{
    /** Hoje só a espera; liberação, escolha e voto são as histórias H7 e H8. */
    public function __invoke(Terminal $terminal): View
    {
        return view('urna.espera', ['terminal' => $terminal]);
    }
}
