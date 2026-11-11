<?php

namespace App\Support;

use App\Models\Cargo;
use App\Models\Eleicao;
use Illuminate\Support\Facades\DB;

/**
 * Alterações na lista de cargos de uma eleição (H13). A ordem fica sempre 1, 2, 3... sem buracos.
 *
 * Cada operação trava a linha da eleição (lockForUpdate) e confere, DENTRO da transação, se
 * já há chapa inscrita: depois da primeira inscrição os cargos congelam (D14). A inscrição
 * (H14, H15) deve travar a mesma linha, para as duas coisas não acontecerem ao mesmo tempo.
 */
class CargosDaEleicao
{
    public function __construct(private readonly Eleicao $eleicao) {}

    public function adicionar(string $titulo): Cargo
    {
        return $this->alterando(function (Eleicao $eleicao) use ($titulo) {
            return $eleicao->cargos()->create([
                'titulo' => $titulo,
                'ordem' => ($eleicao->cargos()->max('ordem') ?? 0) + 1,
            ]);
        });
    }

    public function renomear(Cargo $cargo, string $titulo): void
    {
        $this->alterando(fn () => $cargo->update(['titulo' => $titulo]));
    }

    /** Troca o cargo de lugar com o vizinho de cima (-1) ou de baixo (+1). Nas pontas, não faz nada. */
    public function mover(Cargo $cargo, int $passo): void
    {
        $this->alterando(function (Eleicao $eleicao) use ($cargo, $passo) {
            $vizinho = $eleicao->cargos()->where('ordem', $cargo->ordem + $passo)->first();
            if (! $vizinho) {
                return;
            }

            // UNIQUE (eleicao_id, ordem): passa por 0 (nunca usado) para trocar sem colidir.
            $ordem = $cargo->ordem;
            $cargo->update(['ordem' => 0]);
            $vizinho->update(['ordem' => $ordem]);
            $cargo->update(['ordem' => $ordem + $passo]);
        });
    }

    /** Remove e fecha o buraco: os cargos de baixo sobem uma posição. */
    public function remover(Cargo $cargo): void
    {
        $this->alterando(function (Eleicao $eleicao) use ($cargo) {
            $ordem = $cargo->ordem;
            $cargo->delete();

            // Um por um, em ordem crescente: cada um ocupa a posição que acabou de vagar.
            $eleicao->cargos()->where('ordem', '>', $ordem)->get()
                ->each(fn (Cargo $abaixo) => $abaixo->update(['ordem' => $abaixo->ordem - 1]));
        });
    }

    /**
     * Copia os cargos de outra eleição, na mesma ordem. Só para lista vazia: misturar com
     * cargos já digitados criaria títulos repetidos e uma ordem difícil de prever.
     *
     * @return int quantos cargos foram copiados
     */
    public function copiarDe(Eleicao $origem): int
    {
        return $this->alterando(function (Eleicao $eleicao) use ($origem) {
            if ($eleicao->cargos()->exists()) {
                throw new CargosBloqueados('A cópia só pode ser feita com a lista vazia. Remova os cargos atuais antes de copiar.');
            }

            $origem->cargos->each(fn (Cargo $cargo) => $eleicao->cargos()->create([
                'titulo' => $cargo->titulo,
                'ordem' => $cargo->ordem,
            ]));

            return $origem->cargos->count();
        });
    }

    /** Executa a alteração com a eleição travada, se os cargos ainda puderem mudar. */
    private function alterando(callable $alteracao): mixed
    {
        return DB::transaction(function () use ($alteracao) {
            $eleicao = Eleicao::whereKey($this->eleicao->getKey())->lockForUpdate()->firstOrFail();

            if ($eleicao->temInscricoes()) {
                throw new CargosBloqueados('Já há chapa inscrita nesta eleição: os cargos não podem mais mudar, porque as chapas inscritas ficariam incompletas.');
            }

            return $alteracao($eleicao);
        });
    }
}
