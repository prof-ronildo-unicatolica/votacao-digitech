<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcaoAuditoria;
use App\Http\Controllers\Controller;
use App\Http\Requests\EleicaoRequest;
use App\Models\Eleicao;
use App\Support\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** CRUD de eleições e ativação exclusiva (H2). */
class EleicaoController extends Controller
{
    /** O que a auditoria guarda de uma eleição criada ou excluída (H11 fase 2). */
    private const CAMPOS = ['titulo', 'inscricao_abre_em', 'inscricao_fecha_em', 'votacao_abre_em', 'votacao_fecha_em'];

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
        $eleicao = DB::transaction(function () use ($request) {
            $eleicao = Eleicao::create($request->validated());
            Auditoria::registrar(AcaoAuditoria::EleicaoCriada, $eleicao, Auditoria::campos($eleicao, self::CAMPOS));

            // A primeira eleição cadastrada já nasce ativa: é o caso comum.
            if (! Eleicao::atual()) {
                $eleicao->ativar();
                Auditoria::registrar(AcaoAuditoria::EleicaoAtivada, $eleicao, ['titulo' => $eleicao->titulo]);
            }

            return $eleicao;
        });

        return redirect()->route('admin.eleicoes.index')->with('status', "Eleição \"{$eleicao->titulo}\" cadastrada.");
    }

    public function edit(Eleicao $eleicao): View
    {
        return view('admin.eleicoes.form', ['eleicao' => $eleicao]);
    }

    public function update(EleicaoRequest $request, Eleicao $eleicao): RedirectResponse
    {
        DB::transaction(function () use ($request, $eleicao) {
            $eleicao->update($request->validated());
            if ($mudancas = Auditoria::alteracoes($eleicao)) {
                Auditoria::registrar(AcaoAuditoria::EleicaoAlterada, $eleicao, $mudancas);
            }
        });

        return redirect()->route('admin.eleicoes.index')->with('status', "Eleição \"{$eleicao->titulo}\" atualizada.");
    }

    /** Só apaga eleição cadastrada errada: sem chapas e sem ninguém liberado para votar. */
    public function destroy(Eleicao $eleicao): RedirectResponse
    {
        if ($eleicao->chapas()->exists() || $eleicao->sessoes()->exists()) {
            return back()->withErrors(['geral' => 'Esta eleição já tem chapas ou eleitores liberados e não pode ser excluída. Desative-a em vez de excluir.']);
        }

        DB::transaction(function () use ($eleicao) {
            // Antes do DELETE: o evento aponta para a eleição, e o banco anula o vínculo ao apagar.
            Auditoria::registrar(AcaoAuditoria::EleicaoExcluida, $eleicao, Auditoria::campos($eleicao, self::CAMPOS));
            $eleicao->delete();
        });

        return redirect()->route('admin.eleicoes.index')->with('status', "Eleição \"{$eleicao->titulo}\" excluída.");
    }

    public function ativar(Eleicao $eleicao): RedirectResponse
    {
        DB::transaction(function () use ($eleicao) {
            $anterior = Eleicao::atual();
            $eleicao->ativar();
            if ($anterior && ! $anterior->is($eleicao)) {
                Auditoria::registrar(AcaoAuditoria::EleicaoDesativada, $anterior, ['titulo' => $anterior->titulo, 'motivo' => 'outra eleição foi ativada']);
            }
            Auditoria::registrar(AcaoAuditoria::EleicaoAtivada, $eleicao, ['titulo' => $eleicao->titulo]);
        });

        return back()->with('status', "\"{$eleicao->titulo}\" agora é a eleição ativa.");
    }

    public function desativar(Eleicao $eleicao): RedirectResponse
    {
        DB::transaction(function () use ($eleicao) {
            $eleicao->desativar();
            Auditoria::registrar(AcaoAuditoria::EleicaoDesativada, $eleicao, ['titulo' => $eleicao->titulo]);
        });

        return back()->with('status', "\"{$eleicao->titulo}\" foi desativada. Nenhuma eleição está ativa.");
    }
}
