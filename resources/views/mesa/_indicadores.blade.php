{{-- Indicadores da mesa (H6, H9): comparecimento, terminais livres e encerramento. Nunca votos por chapa. --}}
@php($livres = $terminais->filter(fn ($t) => $t->estado() === 'livre'))
@if ($eleicao)
    <div class="row g-3 mb-5">
        <div class="col-sm-4">
            <x-indicador rotulo="Comparecimento" :valor="$votaram"
                :detalhe="'de '.$aptos.' aptos · '.($aptos ? round(100 * $votaram / $aptos) : 0).'%'"
                :progresso="$aptos ? 100 * $votaram / $aptos : 0" />
        </div>
        <div class="col-sm-4"><x-indicador rotulo="Terminais livres" :valor="$livres->count()" :detalhe="'de '.$terminais->count()" /></div>
        <div class="col-sm-4"><x-indicador rotulo="Votação encerra" :valor="$eleicao->votacao_fecha_em?->format('H:i') ?? '—'" :detalhe="$eleicao->votacao_fecha_em?->format('d/m/Y')" /></div>
    </div>
@endif
