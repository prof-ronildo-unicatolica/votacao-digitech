{{--
    Estado de um terminal no painel do mesário (H5, H9). Sempre cor + ícone + palavra.
    Estados: livre, liberado, votando, inativo. Uso: <x-status-terminal numero="2" estado="liberado" detalhe="Maria Oliveira" />
--}}
@props(['numero', 'estado' => 'inativo', 'detalhe' => null])

@php
    [$rotulo, $icone] = [
        'livre' => ['Livre', 'check-circle-fill'],
        'liberado' => ['Liberado', 'unlock-fill'],
        'votando' => ['Votando', 'hourglass-split'],
        'inativo' => ['Inativo', 'dash-circle'],
    ][$estado] ?? ['Inativo', 'dash-circle'];
@endphp

<article {{ $attributes->class('dt-terminal') }} data-estado="{{ $estado }}" aria-label="Terminal {{ $numero }}: {{ $rotulo }}">
    <div class="dt-terminal-numero">Terminal {{ $numero }}</div>
    <div class="dt-terminal-estado"><i class="bi bi-{{ $icone }}" aria-hidden="true"></i>{{ $rotulo }}</div>
    @if ($detalhe)<div class="dt-terminal-detalhe">{{ $detalhe }}</div>@endif
    {{ $slot }}
</article>
