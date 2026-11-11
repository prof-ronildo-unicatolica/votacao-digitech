<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Urna do computador pareado (D16): /urna, sem ID na URL. O middleware `terminal` confere o
 * cookie de pareamento e coloca o terminal na requisição; sem ele, a resposta é 403.
 */
class UrnaController extends Controller
{
    /** Hoje só a espera; liberação, escolha e voto são as histórias H7 e H8. */
    public function __invoke(Request $request): View
    {
        return view('urna.espera', ['terminal' => $request->attributes->get('terminal')]);
    }

    /**
     * GET /urna/status: a urna fará polling aqui a cada 3 s (H7).
     * Quando H6 definir a situação da sessão, devolve "liberado" e o que a urna precisa mostrar.
     */
    public function status(Request $request): JsonResponse
    {
        $terminal = $request->attributes->get('terminal');

        return response()->json(['terminal' => $terminal->numero, 'estado' => $terminal->estado()]);
    }
}
