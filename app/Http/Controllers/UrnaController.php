<?php

namespace App\Http\Controllers;

use App\Models\Eleicao;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Support\LiberacaoDeEleitor;
use App\Support\RegistroDeVoto;
use App\Support\VotoRecusado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * H7 · Urna do computador pareado (D16): /urna, sem ID na URL. O middleware `terminal` confere o
 * cookie de pareamento e coloca o terminal na requisição; sem ele, a resposta é 403.
 *
 * H8 · Votar: cédula → confirmar → votar → registrado (D24).
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
     * POST /urna/confirmar: mostra a opção escolhida para o eleitor confirmar. Não grava nada,
     * nem na sessão HTTP (D24): a escolha só existe no formulário da tela.
     */
    public function confirmar(Request $request, RegistroDeVoto $registro): View|RedirectResponse
    {
        $terminal = $request->attributes->get('terminal');

        try {
            $sessao = $registro->sessaoDaTela($terminal, (string) $request->input('versao'));
            if (! $sessao->emAndamento()) {
                throw new VotoRecusado('O tempo desta liberação acabou ou ela foi cancelada. Chame o mesário.');
            }
            [$tipo, $chapa] = $registro->opcao($sessao->eleicao, (string) $request->input('escolha'));
        } catch (VotoRecusado $e) {
            return redirect()->route('urna')->with('aviso', $e->getMessage());
        }

        return view('urna.confirmacao', [
            'terminal' => $terminal,
            'sessao' => $sessao,
            'tipo' => $tipo,
            'chapa' => $chapa,
            'escolha' => (string) $request->input('escolha'),
            'versao' => $sessao->versao(),
        ]);
    }

    /** POST /urna/votar (H8, D24). Sem validate(): erros de validação guardariam a escolha na sessão HTTP. */
    public function votar(Request $request, RegistroDeVoto $registro): RedirectResponse
    {
        try {
            $registro->votar($request->attributes->get('terminal'), (string) $request->input('versao'), (string) $request->input('escolha'));
        } catch (VotoRecusado $e) {
            return redirect()->route('urna')->with('aviso', $e->getMessage());
        }

        return redirect()->route('urna.registrado');
    }

    /** "Voto registrado", sem dizer em quem. Volta para a espera em 5 segundos. */
    public function registrado(Request $request): View
    {
        return view('urna.registrado', ['terminal' => $request->attributes->get('terminal')]);
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
