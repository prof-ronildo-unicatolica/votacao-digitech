<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FaseEleicao;
use App\Enums\SituacaoChapa;
use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Support\HomologacaoDeChapa;
use App\Support\InscricaoRecusada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** H3 · A comissão analisa as chapas: homologa, indefere com motivo, reabre, corrige e exclui. */
class ChapaController extends Controller
{
    public function __construct(private readonly HomologacaoDeChapa $homologacao) {}

    public function index(Request $request): View
    {
        $eleicoes = Eleicao::orderByDesc('inscricao_abre_em')->get(['id', 'titulo', 'ativa', 'inscricao_abre_em']);
        // A lista só tem o necessário para o seletor: a escolhida é recarregada inteira, com as
        // datas, senão a fase sai vazia e a tela vira "somente leitura" (achado pelo teste E2E).
        $eleicao = $eleicoes->firstWhere('id', $request->query('eleicao')) ?? Eleicao::atual() ?? $eleicoes->first();
        $eleicao = $eleicao ? Eleicao::find($eleicao->id) : null;
        $filtro = SituacaoChapa::tryFrom((string) $request->query('situacao'));

        $chapas = $eleicao
            ? $eleicao->chapas()->with(['composicao', 'analisadaPor:id,name'])->when($filtro, fn ($q) => $q->where('situacao', $filtro))->get()
            : collect();

        return view('admin.chapas.index', [
            'eleicao' => $eleicao,
            'eleicoes' => $eleicoes,
            'chapas' => $chapas,
            'filtro' => $filtro,
            'contagem' => $eleicao ? $eleicao->chapas()->toBase()->selectRaw('situacao, count(*) as total')->groupBy('situacao')->pluck('total', 'situacao') : collect(),
            'bloqueio' => $eleicao ? HomologacaoDeChapa::bloqueio($eleicao) : null,
            'composicaoAberta' => $eleicao?->fase() === FaseEleicao::InscricoesAbertas,
        ]);
    }

    public function homologar(Request $request, Chapa $chapa): RedirectResponse
    {
        return $this->executar($chapa, fn () => $this->homologacao->homologar($chapa, $request->user()), "Chapa {$chapa->numero} · {$chapa->nome} homologada: ela vai aparecer na urna.");
    }

    public function indeferir(Request $request, Chapa $chapa): RedirectResponse
    {
        return $this->executar($chapa, fn () => $this->homologacao->indeferir($chapa, $request->user(), (string) $request->input('motivo')), "Chapa {$chapa->numero} · {$chapa->nome} indeferida.");
    }

    public function reabrir(Request $request, Chapa $chapa): RedirectResponse
    {
        return $this->executar($chapa, fn () => $this->homologacao->reabrir($chapa, $request->user()), "Análise da chapa {$chapa->numero} reaberta: ela voltou para pendente.");
    }

    public function trocarCandidato(Request $request, Chapa $chapa, Cargo $cargo): RedirectResponse
    {
        abort_unless($cargo->eleicao_id === $chapa->eleicao_id, 404);

        $mensagem = function (bool $voltou) use ($chapa, $cargo) {
            return "Candidato a {$cargo->titulo} da chapa {$chapa->numero} trocado.".($voltou ? ' A chapa estava homologada e voltou para pendente: analise de novo.' : '');
        };

        return $this->executar($chapa, fn () => $this->homologacao->trocarCandidato($chapa, $cargo, (string) $request->input('matricula'), $request->user()), $mensagem, "cargo-{$cargo->id}");
    }

    public function destroy(Chapa $chapa): RedirectResponse
    {
        return $this->executar($chapa, fn () => $this->homologacao->excluir($chapa), "Chapa {$chapa->numero} · {$chapa->nome} excluída.");
    }

    /**
     * Roda a operação e volta para a lista da mesma eleição. Os erros de campo voltam com a
     * chapa (e o cargo) que os causou, para a tela abrir o formulário certo.
     */
    private function executar(Chapa $chapa, callable $operacao, string|\Closure $mensagem, ?string $alvo = null): RedirectResponse
    {
        $voltar = route('admin.chapas.index', ['eleicao' => $chapa->eleicao_id]).'#chapa-'.$chapa->numero;

        try {
            $resultado = $operacao();
        } catch (InscricaoRecusada $e) {
            return redirect()->to($voltar)
                ->withErrors($e->erros)
                ->withInput(['chapa_id' => $chapa->id, 'alvo' => $alvo ?? ''] + request()->only('motivo', 'matricula'));
        }

        return redirect()->to($voltar)->with('status', $mensagem instanceof \Closure ? $mensagem($resultado) : $mensagem);
    }
}
