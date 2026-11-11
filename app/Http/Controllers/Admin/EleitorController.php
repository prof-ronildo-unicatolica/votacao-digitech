<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EleitorRequest;
use App\Models\Eleitor;
use App\Support\ImportadorEleitores;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Admin monta o eleitorado: um a um ou por CSV (H4). */
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
        $eleitor = Eleitor::create($request->validated());

        return redirect()->route('admin.eleitores.index')->with('status', "{$eleitor->nome} ({$eleitor->matricula}) cadastrado.");
    }

    public function edit(Eleitor $eleitor): View
    {
        return view('admin.eleitores.form', ['eleitor' => $eleitor]);
    }

    public function update(EleitorRequest $request, Eleitor $eleitor): RedirectResponse
    {
        $eleitor->update($request->validated());

        return redirect()->route('admin.eleitores.index')->with('status', "{$eleitor->nome} atualizado.");
    }

    /** Quem já foi liberado (ata) ou é candidato não pode ser apagado; o banco também bloqueia. */
    public function destroy(Eleitor $eleitor): RedirectResponse
    {
        if ($motivo = $eleitor->motivoParaNaoExcluir()) {
            return back()->withErrors(['geral' => $motivo]);
        }

        try {
            $eleitor->delete();
        } catch (QueryException) {
            // Liberação ou candidatura gravada entre a checagem e o DELETE.
            return back()->withErrors(['geral' => "{$eleitor->nome} passou a ter liberação ou candidatura e não pode mais ser excluído."]);
        }

        return redirect()->route('admin.eleitores.index')->with('status', "{$eleitor->nome} foi excluído.");
    }

    public function importarForm(): View
    {
        return view('admin.eleitores.importar', ['relatorio' => session('importacao')]);
    }

    public function importar(Request $request, ImportadorEleitores $importador): RedirectResponse
    {
        $request->validate(
            ['arquivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048']],
            ['arquivo.mimes' => 'Envie um arquivo .csv (no Excel: Salvar como → CSV UTF-8).'],
            ['arquivo' => 'arquivo'],
        );

        try {
            $relatorio = $importador->importar($request->file('arquivo')->get());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['arquivo' => $e->getMessage()]);
        }

        return redirect()->route('admin.eleitores.importar')->with('importacao', $relatorio);
    }

    public function modelo(): BinaryFileResponse
    {
        return response()->download(base_path('docs/exemplos/eleitores.csv'), 'eleitores-modelo.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
