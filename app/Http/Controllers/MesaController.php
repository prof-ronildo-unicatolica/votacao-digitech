<?php

namespace App\Http\Controllers;

use App\Enums\AcaoAuditoria;
use App\Enums\SituacaoSessao;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Support\Auditoria;
use App\Support\LiberacaoDeEleitor;
use App\Support\LiberacaoRecusada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * H6 · Mesa receptora: o mesário confere a matrícula, escolhe o terminal e libera o voto;
 * vê os terminais e cancela uma sessão que ainda não votou. O admin também entra (D15).
 */
class MesaController extends Controller
{
    public function __construct(private readonly LiberacaoDeEleitor $liberacao) {}

    public function index(Request $request): View
    {
        $eleicao = Eleicao::atual();
        $terminais = Terminal::orderBy('numero')->with('sessaoEmAndamento.eleitor')->get();

        $consulta = null;
        $matricula = trim((string) $request->query('matricula'));
        if ($eleicao && $matricula !== '') {
            try {
                $consulta = $this->liberacao->consultar($eleicao, $matricula, $request->user());
            } catch (LiberacaoRecusada $e) {
                $consulta = ['erro' => $e->getMessage()];
            }
        }

        return view('mesa.index', [
            'eleicao' => $eleicao,
            'bloqueio' => $eleicao ? LiberacaoDeEleitor::bloqueio($eleicao) : 'Não há eleição ativa. A comissão precisa ativar a eleição.',
            'terminais' => $terminais,
            'matricula' => $matricula,
            'consulta' => $consulta,
            'votaram' => $eleicao ? $eleicao->sessoes()->where('situacao', SituacaoSessao::Votou)->count() : 0,
            'aptos' => Eleitor::count(),
        ]);
    }

    public function liberar(Request $request): RedirectResponse
    {
        $dados = $request->validate(
            ['matricula' => ['required', 'string', 'max:30'], 'terminal_id' => ['required', 'string']],
            ['terminal_id.required' => 'Escolha o terminal.', 'matricula.required' => 'Digite a matrícula do eleitor.'],
        );

        $eleicao = Eleicao::atual();
        $terminal = Terminal::find($dados['terminal_id']);
        if (! $eleicao || ! $terminal) {
            return $this->voltar($dados['matricula'])->withErrors(['geral' => $eleicao ? 'Terminal não encontrado.' : 'Não há eleição ativa.']);
        }

        try {
            $sessao = $this->liberacao->liberar($eleicao, $dados['matricula'], $terminal, $request->user());
        } catch (LiberacaoRecusada $e) {
            // A transação foi desfeita, mas a tentativa aconteceu (D22): ex. liberar quem já votou.
            Auditoria::registrarTentativa(AcaoAuditoria::LiberacaoRecusada, [
                'matricula' => preg_replace('/\s+/', '', $dados['matricula']),
                'terminal' => $terminal->numero,
                'motivo' => $e->getMessage(),
            ], $eleicao->id);

            return $this->voltar($dados['matricula'])->withErrors(['geral' => $e->getMessage()]);
        }

        return redirect()->route('mesario')->with('status', "Terminal {$sessao->terminal->numero} liberado para {$sessao->eleitor->nome} (vale até as {$sessao->expira_em->format('H:i')}).");
    }

    public function cancelar(SessaoVotacao $sessao): RedirectResponse
    {
        try {
            $this->liberacao->cancelar($sessao);
        } catch (LiberacaoRecusada $e) {
            return redirect()->route('mesario')->withErrors(['geral' => $e->getMessage()]);
        }

        $sessao->load(['terminal', 'eleitor']);

        return redirect()->route('mesario')->with('status', "Sessão de {$sessao->eleitor->nome} no terminal {$sessao->terminal->numero} cancelada. A liberação pode ser feita de novo.");
    }

    /** Volta para a mesa com a matrícula ainda conferida na tela. */
    private function voltar(string $matricula): RedirectResponse
    {
        return redirect()->route('mesario', ['matricula' => $matricula]);
    }
}
