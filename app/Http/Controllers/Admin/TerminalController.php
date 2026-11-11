<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TerminalRequest;
use App\Models\Terminal;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Admin cadastra os terminais e desativa pareamentos (H5). Ativar é no computador da urna. */
class TerminalController extends Controller
{
    public function index(): View
    {
        return view('admin.terminais.index', [
            'terminais' => Terminal::withCount('sessoes')->with('sessaoEmAndamento.eleitor')->orderBy('numero')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.terminais.form', [
            'terminal' => new Terminal(['numero' => (Terminal::max('numero') ?? 0) + 1]),
        ]);
    }

    public function store(TerminalRequest $request): RedirectResponse
    {
        $terminal = Terminal::create($request->validated());

        return redirect()->route('admin.terminais.index')->with('status', "Terminal {$terminal->numero} cadastrado. Ative-o no computador da urna.");
    }

    public function edit(Terminal $terminal): View
    {
        return view('admin.terminais.form', ['terminal' => $terminal]);
    }

    public function update(TerminalRequest $request, Terminal $terminal): RedirectResponse
    {
        $terminal->update($request->validated());

        return redirect()->route('admin.terminais.index')->with('status', "Terminal {$terminal->numero} atualizado.");
    }

    /** Apaga o hash: o computador pareado perde o acesso na próxima consulta da urna. */
    public function desativar(Terminal $terminal): RedirectResponse
    {
        $terminal->desativar();

        return back()->with('status', "Terminal {$terminal->numero} desativado. O computador precisa ser ativado de novo para votar.");
    }

    /** Terminal com sessões faz parte da ata e não pode ser apagado (o banco também bloqueia). */
    public function destroy(Terminal $terminal): RedirectResponse
    {
        if ($terminal->sessoes()->exists()) {
            return back()->withErrors(['geral' => "O terminal {$terminal->numero} já recebeu eleitores e faz parte da ata: não pode ser excluído. Desative em vez de excluir."]);
        }

        try {
            $terminal->delete();
        } catch (QueryException) {
            return back()->withErrors(['geral' => "O terminal {$terminal->numero} passou a ter sessões e não pode mais ser excluído."]);
        }

        return redirect()->route('admin.terminais.index')->with('status', "Terminal {$terminal->numero} excluído.");
    }
}
