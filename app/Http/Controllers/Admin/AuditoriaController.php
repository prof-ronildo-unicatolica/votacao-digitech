<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcaoAuditoria;
use App\Http\Controllers\Controller;
use App\Models\Eleicao;
use App\Models\EventoAuditoria;
use App\Models\User;
use App\Support\Auditoria;
use App\Support\FiltroAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * H11 fase 3 · Tela de auditoria (só admin): filtros, detalhes e exportação em CSV.
 * Só lê: os eventos são gravados por App\Support\Auditoria (D22).
 */
class AuditoriaController extends Controller
{
    public const POR_PAGINA = 50;

    public function index(Request $request): View
    {
        $filtro = FiltroAuditoria::daRequisicao($request);

        return view('admin.auditoria.index', [
            'filtro' => $filtro,
            'eventos' => $filtro->consulta()->paginate(self::POR_PAGINA)->withQueryString(),
            'eleicoes' => Eleicao::orderByDesc('votacao_abre_em')->get(['id', 'titulo', 'ativa']),
            'usuarios' => User::whereIn('id', EventoAuditoria::query()->whereNotNull('user_id')->distinct()->select('user_id'))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** O CSV do filtro atual (todas as páginas), para anexar à ata. A exportação também é auditada. */
    public function csv(Request $request): Response
    {
        $filtro = FiltroAuditoria::daRequisicao($request);

        $saida = fopen('php://temp', 'r+');
        fwrite($saida, "\u{FEFF}");
        fputcsv($saida, ['Quando', 'Ação', 'Código', 'Quem', 'IP', 'Eleição', 'Alvo', 'Alvo (id)', 'Detalhes'], ';', '"', '');
        $filtro->consulta()->with('eleicao:id,titulo')->lazy(500)->each(fn (EventoAuditoria $e) => fputcsv($saida, [
            $e->ocorrido_em->format('d/m/Y H:i:s'),
            $e->acao->rotulo(),
            $e->acao->value,
            $e->ator,
            $e->ip,
            $e->eleicao?->titulo,
            $e->alvo_tipo,
            $e->alvo_id,
            json_encode($e->dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], ';', '"', ''));
        rewind($saida);
        $conteudo = stream_get_contents($saida);

        // Depois de montar: o próprio evento de exportação não entra no arquivo que ele descreve.
        $eleicao = $filtro->get('eleicao');
        Auditoria::registrar(AcaoAuditoria::AuditoriaExportada, dados: [
            'filtros' => $filtro->valores,
            'sha256' => hash('sha256', $conteudo),
        ], eleicaoId: $eleicao !== 'todas' && Eleicao::whereKey($eleicao)->exists() ? $eleicao : null);

        return response($conteudo, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="auditoria-'.now()->format('Y-m-d-His').'.csv"',
        ]);
    }
}
