<?php

namespace App\Support;

use App\Enums\FaseEleicao;
use App\Enums\SituacaoSessao;
use App\Enums\TipoVoto;
use App\Models\Candidato;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use Illuminate\Support\Collection;

/**
 * Busca no banco os números de uma eleição e os entrega à `Apuracao` (H10). A tela, o CSV e a
 * ata usam esta classe, para os três mostrarem exatamente os mesmos números.
 *
 * - **Antes do fim da votação** só existe o comparecimento (`comparecimento()`): contar votos por
 *   chapa com a urna aberta seria uma parcial, e parcial influencia quem ainda vai votar (D26).
 * - **Só chapas homologadas** (`Eleicao::chapasNaUrna`, D20) entram na apuração.
 */
final class ResultadoDaEleicao
{
    /**
     * @param  Collection<int, Chapa>  $chapas  homologadas, pelo número, com a composição
     * @param  Collection<int, Candidato>  $eleitos  composição da vencedora, pela ordem dos cargos
     */
    private function __construct(
        public readonly Eleicao $eleicao,
        public readonly Collection $chapas,
        public readonly Apuracao $apuracao,
        public readonly Collection $eleitos,
    ) {}

    /** O resultado só sai depois do fim da votação. Eleição sem as datas nunca encerra. */
    public static function liberado(Eleicao $eleicao): bool
    {
        return $eleicao->fase() === FaseEleicao::Encerrada;
    }

    /** @return array{votaram: int, aptos: int} */
    public static function comparecimento(Eleicao $eleicao): array
    {
        return [
            'votaram' => $eleicao->sessoes()->where('situacao', SituacaoSessao::Votou)->count(),
            'aptos' => Eleitor::count(),
        ];
    }

    /** Apura a eleição. Quem chama confere `liberado()` antes. */
    public static function apurar(Eleicao $eleicao): self
    {
        $chapas = $eleicao->chapasNaUrna()->with('composicao')->get();

        $contagem = $eleicao->votos()
            ->selectRaw('tipo, chapa_id, count(*) as votos')
            ->groupBy('tipo', 'chapa_id')
            ->get();
        $porChapa = $contagem->where('tipo', TipoVoto::Chapa)->pluck('votos', 'chapa_id');
        $doTipo = fn (TipoVoto $tipo) => (int) $contagem->where('tipo', $tipo)->sum('votos');

        $comparecimento = self::comparecimento($eleicao);
        $apuracao = new Apuracao(
            // Chave = id da chapa. Chapa sem voto entra com 0; voto em chapa fora da lista não
            // entra aqui, e a conferência (total x comparecimento) acusa a diferença.
            $chapas->mapWithKeys(fn (Chapa $c) => [$c->id => (int) ($porChapa[$c->id] ?? 0)])->all(),
            $doTipo(TipoVoto::Branco),
            $doTipo(TipoVoto::Nulo),
            $comparecimento['votaram'],
            $comparecimento['aptos'],
        );

        $vencedora = $chapas->firstWhere('id', $apuracao->vencedora());

        return new self($eleicao, $chapas, $apuracao, $vencedora?->composicao ?? collect());
    }

    public function vencedora(): ?Chapa
    {
        return $this->chapas->firstWhere('id', $this->apuracao->vencedora());
    }

    /** @return Collection<int, Chapa> */
    public function empatadas(): Collection
    {
        return $this->apuracao->empate() ? $this->chapas->whereIn('id', $this->apuracao->primeiras)->values() : collect();
    }

    /**
     * Linhas da tabela, na ordem do número da chapa, com brancos e nulos no fim. A cor segue a
     * chapa (posição pelo número), nunca a colocação (docs/IDENTIDADE_VISUAL.md).
     *
     * @return list<array{rotulo: string, numero: int|null, votos: int, total: float, validos: float|null, cor: string}>
     */
    public function linhas(): array
    {
        $doTotal = $this->apuracao->percentuaisDoTotal();
        $dosValidos = $this->apuracao->percentuaisDosValidos();
        $a = $this->apuracao;

        $linhas = $this->chapas->values()->map(fn (Chapa $c, int $i) => [
            'rotulo' => $c->numero.' · '.$c->nome,
            'numero' => $c->numero,
            'votos' => $a->votosPorChapa[$c->id],
            'total' => $doTotal[$c->id],
            'validos' => $dosValidos[$c->id],
            'cor' => self::cor($i),
        ])->all();

        $linhas[] = ['rotulo' => 'Brancos', 'numero' => null, 'votos' => $a->brancos, 'total' => Apuracao::percentual($a->brancos, $a->total), 'validos' => null, 'cor' => self::COR_BRANCO_NULO];
        $linhas[] = ['rotulo' => 'Nulos', 'numero' => null, 'votos' => $a->nulos, 'total' => Apuracao::percentual($a->nulos, $a->total), 'validos' => null, 'cor' => self::COR_BRANCO_NULO];

        return $linhas;
    }

    /**
     * Barras do gráfico: até 6 chapas com cor própria; da 7ª em diante, somadas em "Outras chapas"
     * (a tabela mostra todas). Brancos e nulos sempre separados, em cinza.
     *
     * @return list<array{rotulo: string, votos: int, cor: string}>
     */
    public function grafico(): array
    {
        $linhas = collect($this->linhas());
        [$chapas, $semChapa] = $linhas->partition(fn ($l) => $l['numero'] !== null);

        $barras = $chapas->take(count(self::CORES));
        if ($chapas->count() > count(self::CORES)) {
            $barras->push(['rotulo' => 'Outras chapas', 'votos' => $chapas->slice(count(self::CORES))->sum('votos'), 'cor' => self::COR_BRANCO_NULO]);
        }

        return $barras->concat($semChapa)->map(fn ($l) => ['rotulo' => $l['rotulo'], 'votos' => $l['votos'], 'cor' => $l['cor']])->values()->all();
    }

    /** Paleta de dados (tokens cor.dados.*), pela ordem do número da chapa. */
    private const CORES = ['#2A78D6', '#1BAF7A', '#6250D6', '#EDA100', '#E87BA4', '#008300'];

    private const COR_BRANCO_NULO = '#9E9EA4';

    private static function cor(int $posicao): string
    {
        return self::CORES[$posicao] ?? self::COR_BRANCO_NULO;
    }
}
