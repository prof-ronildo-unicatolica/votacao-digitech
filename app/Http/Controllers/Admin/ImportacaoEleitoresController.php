<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ArquivoEmConferencia;
use App\Support\ArquivoInvalido;
use App\Support\ImportacaoDesatualizada;
use App\Support\ImportacaoRecusada;
use App\Support\ImportadorEleitores;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * H4 · Importar eleitores por CSV, no mesmo fluxo das chapas (D19):
 * enviar (só confere) → conferir (campos com erro, o que é novo, o que muda) →
 * cancelar (rollback), ignorar as linhas com erro ou confirmar (transação única).
 */
class ImportacaoEleitoresController extends Controller
{
    public function create(): View
    {
        return view('admin.eleitores.importar', [
            'emConferencia' => $this->arquivo()->dados(),
            'resultado' => session('importacao_eleitores_resultado'),
        ]);
    }

    public function store(Request $request, ImportadorEleitores $importador): RedirectResponse
    {
        $request->validate(
            ['arquivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048']],
            ['arquivo.mimes' => 'Envie um arquivo .csv (no Excel: Salvar como → CSV UTF-8).', 'arquivo.max' => 'O arquivo pode ter no máximo 2 MB.'],
            ['arquivo' => 'arquivo'],
        );

        $conteudo = $request->file('arquivo')->get();
        try {
            $importador->analisar($conteudo);
        } catch (ArquivoInvalido $e) {
            return back()->withErrors(['arquivo' => $e->erros]);
        }

        $this->arquivo()->guardar($conteudo, $request->file('arquivo')->getClientOriginalName());

        return redirect()->route('admin.eleitores.importar.conferir');
    }

    public function conferir(ImportadorEleitores $importador): View|RedirectResponse
    {
        $conteudo = $this->arquivo()->conteudo();
        if ($conteudo === null) {
            return redirect()->route('admin.eleitores.importar')->with('aviso', 'Nenhum arquivo em conferência. Envie o CSV de novo.');
        }

        return view('admin.eleitores.conferir', [
            'analise' => $importador->analisar($conteudo),
            'arquivo' => $this->arquivo()->dados()['nome'],
        ]);
    }

    public function confirmar(Request $request, ImportadorEleitores $importador): RedirectResponse
    {
        if ($request->input('acao') === 'cancelar') {
            $this->arquivo()->descartar();

            return redirect()->route('admin.eleitores.importar')->with('status', 'Importação cancelada. O arquivo foi descartado e nenhum aluno foi gravado.');
        }

        $request->validate(
            ['acao' => ['required', Rule::in(['importar', 'ignorar'])], 'assinatura' => ['required', 'string']],
            ['acao.required' => 'Escolha o que fazer com as linhas com erro.'],
        );

        $conteudo = $this->arquivo()->conteudo();
        if ($conteudo === null) {
            return redirect()->route('admin.eleitores.importar')->with('aviso', 'Nenhum arquivo em conferência. Envie o CSV de novo.');
        }

        try {
            $resultado = $importador->importar($conteudo, $request->input('assinatura'), $request->input('acao') === 'ignorar');
        } catch (ImportacaoDesatualizada $e) {
            return redirect()->route('admin.eleitores.importar.conferir')->with('aviso', $e->getMessage());
        } catch (ImportacaoRecusada $e) {
            return redirect()->route('admin.eleitores.importar.conferir')->withErrors(['acao' => $e->getMessage()]);
        }

        $this->arquivo()->descartar();

        return redirect()->route('admin.eleitores.importar')->with('importacao_eleitores_resultado', $resultado);
    }

    public function modelo(): BinaryFileResponse
    {
        return response()->download(base_path('docs/exemplos/eleitores.csv'), 'eleitores-modelo.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function arquivo(): ArquivoEmConferencia
    {
        return new ArquivoEmConferencia('importacao_eleitores');
    }
}
