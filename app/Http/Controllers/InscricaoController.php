<?php

namespace App\Http\Controllers;

use App\Enums\FaseEleicao;
use App\Enums\OrigemChapa;
use App\Http\Requests\InscricaoRequest;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Support\InscricaoDeChapa;
use App\Support\InscricaoRecusada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inscrição pública de chapa (H14, D14), sem login, para a eleição ativa.
 * A página não lista outras chapas nem mostra dados de alunos além do nome conferido.
 */
class InscricaoController extends Controller
{
    /** Guarda, na sessão HTTP de quem inscreveu, qual chapa o comprovante mostra. */
    private const SESSAO_COMPROVANTE = 'inscricao.comprovante';

    public function create(): View
    {
        $eleicao = Eleicao::atual();
        $cargos = $eleicao?->cargos()->get() ?? collect();

        if (! $eleicao || $eleicao->fase() !== FaseEleicao::InscricoesAbertas || $cargos->isEmpty()) {
            return view('inscricao.fechada', [
                'eleicao' => $eleicao,
                'mensagem' => match (true) {
                    ! $eleicao => 'Não há eleição com inscrições no momento.',
                    $eleicao->fase() === FaseEleicao::InscricoesAbertas => 'A comissão ainda está definindo os cargos das chapas. Volte em breve.',
                    default => InscricaoDeChapa::mensagemForaDoPeriodo($eleicao),
                },
            ]);
        }

        return view('inscricao.form', [
            'eleicao' => $eleicao,
            'cargos' => $cargos,
            'nomes' => $this->nomesDasMatriculas((array) old('candidatos', [])),
        ]);
    }

    public function store(InscricaoRequest $request, InscricaoDeChapa $inscricao): RedirectResponse
    {
        $eleicao = Eleicao::atual();
        if (! $eleicao) {
            return redirect()->route('inscricao');
        }

        try {
            $chapa = $inscricao->inscrever(
                $eleicao,
                $request->input('nome'),
                (array) $request->input('candidatos', []),
                $request->input('contato_email'),
                $request->input('contato_telefone'),
                OrigemChapa::Formulario,
            );
        } catch (InscricaoRecusada $e) {
            return back()->withInput()->withErrors($e->erros);
        }

        $request->session()->put(self::SESSAO_COMPROVANTE, $chapa->id);

        return redirect()->route('inscricao.comprovante');
    }

    /** Só quem acabou de inscrever (mesma sessão do navegador) vê o comprovante. */
    public function comprovante(Request $request): View|RedirectResponse
    {
        $chapa = Chapa::with('eleicao')->find($request->session()->get(self::SESSAO_COMPROVANTE));

        if (! $chapa) {
            return redirect()->route('inscricao');
        }

        return view('inscricao.comprovante', ['chapa' => $chapa, 'composicao' => $chapa->composicao()->get()]);
    }

    /**
     * GET /inscricao/aluno?matricula=: nome do aluno para conferência no formulário.
     * Só o nome (nada de turma ou e-mail), só com as inscrições abertas, e com limite por IP.
     */
    public function aluno(Request $request): JsonResponse
    {
        if (Eleicao::atual()?->fase() !== FaseEleicao::InscricoesAbertas) {
            return response()->json(['encontrado' => false], 404);
        }

        $nome = Eleitor::where('matricula', trim((string) $request->query('matricula')))->value('nome');

        return response()->json($nome ? ['encontrado' => true, 'nome' => $nome] : ['encontrado' => false]);
    }

    /** @return array<string, string> nome do aluno por matrícula, para mostrar de novo quando o envio volta com erro */
    private function nomesDasMatriculas(array $matriculas): array
    {
        $matriculas = array_filter(array_map(fn ($m) => is_string($m) ? trim($m) : '', $matriculas));

        return $matriculas === [] ? [] : Eleitor::whereIn('matricula', $matriculas)->pluck('nome', 'matricula')->all();
    }
}
