<?php

namespace App\Http\Controllers;

use App\Models\Eleicao;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Support\LiberacaoDeEleitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * H7 · Urna do computador pareado (D16): /urna, sem ID na URL. O middleware `terminal` confere o
 * cookie de pareamento e coloca o terminal na requisição; sem ele, a resposta é 403.
 *
 * A tela é montada no servidor: "aguardando" ou a cédula. Um script consulta /urna/status a
 * cada 3 s e recarrega a página quando a liberação muda (liberou, cancelou, expirou).
 */
class UrnaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $terminal = $request->attributes->get('terminal');
        $sessao = $this->sessaoNaUrna($terminal);

        if (! $sessao) {
            return view('urna.espera', ['terminal' => $terminal, 'versao' => null]);
        }

        // A cédula apareceu: o terminal passa a "votando" no painel da mesa. Só a primeira vez.
        SessaoVotacao::whereKey($sessao->id)->whereNull('exibida_em')->update(['exibida_em' => now()]);

        return view('urna.escolha', [
            'terminal' => $terminal,
            'sessao' => $sessao,
            'eleicao' => $sessao->eleicao,
            'chapas' => $sessao->eleicao->chapasNaUrna()->with('composicao')->get(),
            'versao' => $sessao->versao(),
        ]);
    }

    /**
     * GET /urna/status (polling da urna). Só o terminal pareado chega aqui (middleware), e mesmo
     * assim a resposta não traz o nome do eleitor: só se há liberação e qual (`versao`).
     */
    public function status(Request $request): JsonResponse
    {
        $terminal = $request->attributes->get('terminal');
        $sessao = $this->sessaoNaUrna($terminal);

        return response()->json([
            'terminal' => $terminal->numero,
            'liberada' => $sessao !== null,
            'versao' => $sessao?->versao(),
        ]);
    }

    /** A liberação em andamento neste terminal, na eleição ativa e com a votação aberta. */
    private function sessaoNaUrna(Terminal $terminal): ?SessaoVotacao
    {
        $eleicao = Eleicao::atual();
        if (! $eleicao || LiberacaoDeEleitor::bloqueio($eleicao)) {
            return null;
        }

        return $terminal->sessoes()->emAndamento()->where('eleicao_id', $eleicao->id)->with(['eleitor', 'eleicao'])->first();
    }
}
