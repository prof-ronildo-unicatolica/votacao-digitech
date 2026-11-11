<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
