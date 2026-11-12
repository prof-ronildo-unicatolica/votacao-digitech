{{--
    Botão. Variantes: primario (uma por tela), secundario, contorno, fantasma, perigo.
    Uso: <x-botao icone="unlock">Liberar</x-botao> · <x-botao href="/admin" variante="contorno">Voltar</x-botao>
--}}
@props(['variante' => 'primario', 'tamanho' => null, 'href' => null, 'icone' => null, 'type' => 'button'])

@php
    $classe = [
        'primario' => 'btn-primary',
        'secundario' => 'btn-secundario',
        'contorno' => 'btn-contorno',
        'fantasma' => 'btn-fantasma',
        'perigo' => 'btn-perigo',
    ][$variante] ?? 'btn-primary';
    $classes = ['btn', $classe, 'btn-lg' => $tamanho === 'grande', 'btn-sm' => $tamanho === 'pequeno'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icone)<i class="bi bi-{{ $icone }}" aria-hidden="true"></i>@endif{{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icone)<i class="bi bi-{{ $icone }}" aria-hidden="true"></i>@endif{{ $slot }}
    </button>
@endif
