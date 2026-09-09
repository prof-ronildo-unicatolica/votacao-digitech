<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Terminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    /** GET /api/health — usado no smoke test após o deploy. */
    public function health(): JsonResponse
    {
        try {
            DB::select('select 1');
            $banco = 'ok';
        } catch (\Throwable) {
            $banco = 'erro';
        }

        return response()->json([
            'app' => config('app.name'),
            'ambiente' => app()->environment(),
            'banco' => $banco,
            'hora' => now()->toIso8601String(),
        ], $banco === 'ok' ? 200 : 503);
    }

    /** GET /api/terminais/{terminal}/status — a urna faz polling aqui. */
    public function terminal(Terminal $terminal): JsonResponse
    {
        $sessao = $terminal->sessaoAberta;

        return response()->json([
            'terminal' => $terminal->numero,
            'liberada' => $sessao !== null,
            'eleitor' => $sessao?->eleitor?->nome,
            'expira_em' => $sessao?->expira_em?->toIso8601String(),
        ]);
    }
}
