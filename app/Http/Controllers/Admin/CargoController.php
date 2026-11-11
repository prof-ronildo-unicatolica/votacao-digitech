<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CargoRequest;
use App\Models\Cargo;
use App\Models\Eleicao;
use App\Support\CargosBloqueados;
use App\Support\CargosDaEleicao;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cargos que toda chapa da eleição precisa preencher (H13, D13). */
class CargoController extends Controller
{
    /** Item "Cargos" do menu: abre os cargos da eleição ativa. */
    public function atalho(): RedirectResponse
    {
        $eleicao = Eleicao::atual();

        return $eleicao
            ? redirect()->route('admin.eleicoes.cargos.index', $eleicao)
            : redirect()->route('admin.eleicoes.index')->withErrors(['geral' => 'Nenhuma eleição ativa. Escolha a eleição para ver os cargos.']);
    }

    public function index(Eleicao $eleicao): View
    {
        return view('admin.cargos.index', [
            'eleicao' => $eleicao,
            'cargos' => $eleicao->cargos()->get(),
            'bloqueado' => $eleicao->temInscricoes(),
            'origens' => Eleicao::whereKeyNot($eleicao->id)->has('cargos')->withCount('cargos')->orderByDesc('votacao_abre_em')->get(),
        ]);
    }

    public function store(CargoRequest $request, Eleicao $eleicao): RedirectResponse
    {
        return $this->alterar($eleicao, fn (CargosDaEleicao $cargos) => $cargos->adicionar($request->titulo()), "Cargo \"{$request->titulo()}\" adicionado.", $request->campo());
    }

    public function update(CargoRequest $request, Eleicao $eleicao, Cargo $cargo): RedirectResponse
    {
        return $this->alterar($eleicao, fn (CargosDaEleicao $cargos) => $cargos->renomear($cargo, $request->titulo()), "Cargo renomeado para \"{$request->titulo()}\".", $request->campo());
    }

    public function mover(Request $request, Eleicao $eleicao, Cargo $cargo): RedirectResponse
    {
        $passo = $request->validate(['direcao' => ['required', 'in:cima,baixo']])['direcao'] === 'cima' ? -1 : 1;

        return $this->alterar($eleicao, fn (CargosDaEleicao $cargos) => $cargos->mover($cargo, $passo), null);
    }

    public function destroy(Eleicao $eleicao, Cargo $cargo): RedirectResponse
    {
        return $this->alterar($eleicao, fn (CargosDaEleicao $cargos) => $cargos->remover($cargo), "Cargo \"{$cargo->titulo}\" removido.");
    }

    public function copiar(Request $request, Eleicao $eleicao): RedirectResponse
    {
        $dados = $request->validate(
            ['origem_id' => ['required', 'uuid', 'exists:eleicoes,id']],
            ['origem_id.required' => 'Escolha de qual eleição copiar os cargos.'],
        );
        $origem = Eleicao::findOrFail($dados['origem_id']);

        return $this->alterar(
            $eleicao,
            fn (CargosDaEleicao $cargos) => $cargos->copiarDe($origem),
            fn (int $copiados) => "{$copiados} cargos copiados de \"{$origem->titulo}\".",
        );
    }

    /**
     * Aplica a alteração; se os cargos estiverem bloqueados (ou o banco rejeitar), volta com o motivo.
     * A mensagem pode ser uma função que recebe o resultado da alteração.
     */
    private function alterar(Eleicao $eleicao, callable $alteracao, string|\Closure|null $mensagem, string $campoTitulo = 'titulo'): RedirectResponse
    {
        try {
            $resultado = $alteracao(new CargosDaEleicao($eleicao));
        } catch (CargosBloqueados $e) {
            return back()->withErrors(['geral' => $e->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            // Dois admins mexendo ao mesmo tempo, ou título igual com outra grafia que o banco considera igual.
            return back()->withInput()->withErrors([$campoTitulo => 'Esta eleição já tem um cargo com este título.']);
        }

        $redirect = redirect()->route('admin.eleicoes.cargos.index', $eleicao);

        return $mensagem ? $redirect->with('status', $mensagem instanceof \Closure ? $mensagem($resultado) : $mensagem) : $redirect;
    }
}
