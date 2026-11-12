<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcaoAuditoria;
use App\Http\Controllers\Controller;
use App\Http\Requests\EleitorRequest;
use App\Models\Eleitor;
use App\Support\Auditoria;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Admin monta o eleitorado um a um (H4). A importação por CSV fica em ImportacaoEleitoresController. */
class EleitorController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('busca'));

        return view('admin.eleitores.index', [
            'busca' => $busca,
            'total' => Eleitor::count(),
            'eleitores' => Eleitor::busca($busca)
                ->withCount(['sessoes', 'candidaturas'])
                ->orderBy('nome')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.eleitores.form', ['eleitor' => new Eleitor]);
    }

    public function store(EleitorRequest $request): RedirectResponse
    {
        $eleitor = DB::transaction(function () use ($request) {
            $eleitor = Eleitor::create($request->validated());
            Auditoria::registrar(AcaoAuditoria::EleitorCriado, $eleitor, ['matricula' => $eleitor->matricula]);

            return $eleitor;
        });

        return redirect()->route('admin.eleitores.index')->with('status', "{$eleitor->nome} ({$eleitor->matricula}) cadastrado.");
    }

    public function edit(Eleitor $eleitor): View
    {
        return view('admin.eleitores.form', ['eleitor' => $eleitor]);
    }

    public function update(EleitorRequest $request, Eleitor $eleitor): RedirectResponse
    {
        DB::transaction(function () use ($request, $eleitor) {
            $eleitor->update($request->validated());
            if ($mudancas = Auditoria::alteracoes($eleitor)) {
                Auditoria::registrar(AcaoAuditoria::EleitorAlterado, $eleitor, ['matricula' => $eleitor->matricula, 'campos' => $mudancas]);
            }
        });

        return redirect()->route('admin.eleitores.index')->with('status', "{$eleitor->nome} atualizado.");
    }

    /** Quem já foi liberado (ata) ou é candidato não pode ser apagado; o banco também bloqueia. */
    public function destroy(Eleitor $eleitor): RedirectResponse
    {
        if ($motivo = $eleitor->motivoParaNaoExcluir()) {
            return back()->withErrors(['geral' => $motivo]);
        }

        try {
            DB::transaction(function () use ($eleitor) {
                $eleitor->delete();
                Auditoria::registrar(AcaoAuditoria::EleitorExcluido, $eleitor, ['matricula' => $eleitor->matricula]);
            });
        } catch (QueryException) {
            // Liberação ou candidatura gravada entre a checagem e o DELETE.
            return back()->withErrors(['geral' => "{$eleitor->nome} passou a ter liberação ou candidatura e não pode mais ser excluído."]);
        }

        return redirect()->route('admin.eleitores.index')->with('status', "{$eleitor->nome} foi excluído.");
    }
}
