<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EleicaoRequest;
use App\Models\Eleicao;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** CRUD de eleições e ativação exclusiva (H2). */
class EleicaoController extends Controller
{
    public function index(): View
    {
        return view('admin.eleicoes.index', [
            'eleicoes' => Eleicao::withCount(['chapas', 'cargos', 'chapas as homologadas_count' => fn ($q) => $q->homologadas()])->orderByDesc('votacao_abre_em')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.eleicoes.form', ['eleicao' => new Eleicao]);
    }

    public function store(EleicaoRequest $request): RedirectResponse
    {
        $eleicao = Eleicao::create($request->validated());

        // A primeira eleição cadastrada já nasce ativa: é o caso comum.
        if (! Eleicao::atual()) {
            $eleicao->ativar();
        }

        return redirect()->route('admin.eleicoes.index')->with('status', "Eleição \"{$eleicao->titulo}\" cadastrada.");
    }

    public function edit(Eleicao $eleicao): View
    {
        return view('admin.eleicoes.form', ['eleicao' => $eleicao]);
    }

    public function update(EleicaoRequest $request, Eleicao $eleicao): RedirectResponse
    {
        $eleicao->update($request->validated());

        return redirect()->route('admin.eleicoes.index')->with('status', "Eleição \"{$eleicao->titulo}\" atualizada.");
    }

    /** Só apaga eleição cadastrada errada: sem chapas e sem ninguém liberado para votar. */
    public function destroy(Eleicao $eleicao): RedirectResponse
    {
        if ($eleicao->chapas()->exists() || $eleicao->sessoes()->exists()) {
            return back()->withErrors(['geral' => 'Esta eleição já tem chapas ou eleitores liberados e não pode ser excluída. Desative-a em vez de excluir.']);
        }

        $eleicao->delete();

        return redirect()->route('admin.eleicoes.index')->with('status', "Eleição \"{$eleicao->titulo}\" excluída.");
    }

    public function ativar(Eleicao $eleicao): RedirectResponse
    {
        $eleicao->ativar();

        return back()->with('status', "\"{$eleicao->titulo}\" agora é a eleição ativa.");
    }

    public function desativar(Eleicao $eleicao): RedirectResponse
    {
        $eleicao->desativar();

        return back()->with('status', "\"{$eleicao->titulo}\" foi desativada. Nenhuma eleição está ativa.");
    }
}
