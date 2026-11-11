<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcaoAuditoria;
use App\Enums\FaseEleicao;
use App\Http\Controllers\Controller;
use App\Models\Eleicao;
use App\Support\ArquivoEmConferencia;
use App\Support\ArquivoInvalido;
use App\Support\Auditoria;
use App\Support\ImportacaoDesatualizada;
use App\Support\ImportadorChapas;
use App\Support\InscricaoDeChapa;
use App\Support\InscricaoRecusada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * H15 · Importar chapas por CSV, em três passos (D19):
 *
 * 1. Enviar: o arquivo é conferido e guardado (ArquivoEmConferencia). Nada vai para o banco.
 * 2. Conferir: a tela mostra cada campo com erro (linha e coluna) e os nomes padronizados.
 *    O admin escolhe: cancelar (descarta o arquivo, nada é gravado), ignorar as chapas com
 *    erro, ou confirmar.
 * 3. Confirmar: grava as chapas válidas numa transação só. Se algo mudou desde a conferência,
 *    a transação é desfeita (rollback) e a conferência aparece de novo.
 */
class ImportacaoChapasController extends Controller
{
    private const SESSAO = 'importacao_chapas';

    public function create(): View
    {
        $eleicao = Eleicao::atual();

        return view('admin.chapas.importar', [
            'eleicao' => $eleicao,
            'bloqueio' => $this->bloqueio($eleicao),
            'emConferencia' => $this->arquivo()->dados(),
            'resultado' => session('importacao_chapas_resultado'),
        ]);
    }

    public function store(Request $request, ImportadorChapas $importador): RedirectResponse
    {
        $request->validate(
            ['arquivo' => ['required', 'file', 'mimes:csv,txt', 'max:1024']],
            ['arquivo.mimes' => 'Envie um arquivo .csv (no Excel: Salvar como → CSV UTF-8).', 'arquivo.max' => 'O arquivo pode ter no máximo 1 MB.'],
            ['arquivo' => 'arquivo'],
        );

        $eleicao = Eleicao::atual();
        if ($bloqueio = $this->bloqueio($eleicao)) {
            return back()->withErrors(['arquivo' => $bloqueio]);
        }

        $conteudo = $request->file('arquivo')->get();
        try {
            $importador->analisar($eleicao, $conteudo);
        } catch (ArquivoInvalido $e) {
            return back()->withErrors(['arquivo' => $e->erros]);
        }

        $this->arquivo()->guardar($conteudo, $request->file('arquivo')->getClientOriginalName(), ['eleicao_id' => $eleicao->id]);

        return redirect()->route('admin.chapas.importar.conferir');
    }

    public function conferir(ImportadorChapas $importador): View|RedirectResponse
    {
        [$eleicao, $conteudo] = $this->emConferencia();
        if (! $eleicao) {
            return redirect()->route('admin.chapas.importar')->with('aviso', 'Nenhum arquivo em conferência. Envie o CSV de novo.');
        }

        try {
            $analise = $importador->analisar($eleicao, $conteudo);
        } catch (ArquivoInvalido $e) {
            // Os cargos da eleição mudaram depois do envio (H13), por exemplo.
            $this->descartar();

            return redirect()->route('admin.chapas.importar')->withErrors(['arquivo' => $e->erros]);
        }

        return view('admin.chapas.conferir', [
            'eleicao' => $eleicao,
            'analise' => $analise,
            'arquivo' => $this->arquivo()->dados()['nome'],
            'bloqueio' => $this->bloqueio($eleicao),
        ]);
    }

    public function confirmar(Request $request, ImportadorChapas $importador): RedirectResponse
    {
        if ($request->input('acao') === 'cancelar') {
            $this->auditarCancelamento();
            $this->descartar();

            return redirect()->route('admin.chapas.importar')->with('status', 'Importação cancelada. O arquivo foi descartado e nenhuma chapa foi gravada.');
        }

        $request->validate(
            ['acao' => ['required', Rule::in(['importar', 'ignorar'])], 'assinatura' => ['required', 'string']],
            ['acao.required' => 'Escolha o que fazer com as chapas com erro.'],
        );

        [$eleicao, $conteudo] = $this->emConferencia();
        if (! $eleicao) {
            return redirect()->route('admin.chapas.importar')->with('aviso', 'Nenhum arquivo em conferência. Envie o CSV de novo.');
        }

        try {
            $resultado = $importador->importar($eleicao, $conteudo, $request->input('assinatura'), $request->input('acao') === 'ignorar', $this->arquivo()->dados()['nome']);
        } catch (ImportacaoDesatualizada $e) {
            return redirect()->route('admin.chapas.importar.conferir')->with('aviso', $e->getMessage());
        } catch (InscricaoRecusada $e) {
            return redirect()->route('admin.chapas.importar.conferir')->withErrors(['acao' => $e->erros['geral'] ?? $e->getMessage()]);
        } catch (ArquivoInvalido $e) {
            $this->descartar();

            return redirect()->route('admin.chapas.importar')->withErrors(['arquivo' => $e->erros]);
        }

        $this->descartar();

        return redirect()->route('admin.chapas.importar')->with('importacao_chapas_resultado', $resultado);
    }

    public function modelo(): BinaryFileResponse
    {
        return response()->download(base_path('docs/exemplos/chapas.csv'), 'chapas-modelo.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Por que não dá para importar agora, ou null. */
    private function bloqueio(?Eleicao $eleicao): ?string
    {
        if (! $eleicao) {
            return 'Não há eleição ativa. Ative uma eleição antes de importar chapas.';
        }

        return $eleicao->fase() === FaseEleicao::InscricoesAbertas ? null : InscricaoDeChapa::mensagemForaDoPeriodo($eleicao);
    }

    /** Cancelar na conferência é um rollback que a comissão quer ver (H11 fase 2). */
    private function auditarCancelamento(): void
    {
        $dados = $this->arquivo()->dados();
        if ($dados) {
            Auditoria::registrar(AcaoAuditoria::ImportacaoCancelada, dados: ['tipo' => 'chapas', 'arquivo' => $dados['nome'], 'sha256' => hash('sha256', (string) $this->arquivo()->conteudo())], eleicaoId: $dados['eleicao_id'] ?? null);
        }
    }

    private function arquivo(): ArquivoEmConferencia
    {
        return new ArquivoEmConferencia(self::SESSAO);
    }

    /** @return array{0: Eleicao|null, 1: string|null} o arquivo só vale para a eleição em que foi enviado */
    private function emConferencia(): array
    {
        $dados = $this->arquivo()->dados();
        $eleicao = Eleicao::atual();

        if (! $dados || ! $eleicao || $eleicao->id !== $dados['eleicao_id']) {
            $this->descartar();

            return [null, null];
        }

        return [$eleicao, $this->arquivo()->conteudo()];
    }

    private function descartar(): void
    {
        $this->arquivo()->descartar();
    }
}
