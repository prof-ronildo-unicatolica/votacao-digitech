<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Auto-refresh de desenvolvimento, sem Node: o layout base consulta esta rota a cada
 * segundo e recarrega a página quando a "versão" muda (algum arquivo observado foi salvo).
 * Só existe com APP_ENV=local (ver routes/web.php e layouts/base.blade.php).
 */
class RecarregarController extends Controller
{
    private const PASTAS = ['resources/views', 'public/css', 'public/img', 'app', 'routes', 'design/tokens'];

    public function __invoke(): JsonResponse
    {
        $maisRecente = 0;

        foreach (self::PASTAS as $pasta) {
            $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($pasta), RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($arquivos as $arquivo) {
                $maisRecente = max($maisRecente, $arquivo->getMTime());
            }
        }

        return response()->json(['versao' => $maisRecente])->header('Cache-Control', 'no-store');
    }
}
