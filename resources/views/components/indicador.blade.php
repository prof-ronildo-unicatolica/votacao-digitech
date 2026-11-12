{{-- Número de destaque. Uso: <x-indicador rotulo="Comparecimento" valor="142" detalhe="de 500 aptos" :progresso="28.4" /> --}}
@props(['rotulo', 'valor', 'detalhe' => null, 'progresso' => null])

<div {{ $attributes->class(['dt-cartao', 'dt-indicador', 'h-100']) }}>
    <div class="dt-indicador-rotulo">{{ $rotulo }}</div>
    <div class="dt-indicador-valor">{{ $valor }}</div>
    @if ($detalhe)<div class="dt-indicador-detalhe">{{ $detalhe }}</div>@endif
    @if (! is_null($progresso))
        <div class="dt-progresso" role="progressbar" aria-label="{{ $rotulo }}" aria-valuenow="{{ $progresso }}" aria-valuemin="0" aria-valuemax="100">
            <span style="width: {{ min(100, max(0, $progresso)) }}%"></span>
        </div>
    @endif
</div>
