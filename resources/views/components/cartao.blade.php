{{-- Cartão com cabeçalho opcional. Uso: <x-cartao titulo="Liberar eleitor"><x-slot:acoes>...</x-slot:acoes>conteúdo</x-cartao> --}}
@props(['titulo' => null, 'subtitulo' => null, 'acoes' => null, 'semPadding' => false])

<section {{ $attributes->class('dt-cartao') }}>
    @if ($titulo)
        <header class="dt-cartao-cabecalho">
            <div>
                <h2>{{ $titulo }}</h2>
                @if ($subtitulo)<p>{{ $subtitulo }}</p>@endif
            </div>
            {{ $acoes }}
        </header>
    @endif
    <div @class(['dt-cartao-corpo' => ! $semPadding])>{{ $slot }}</div>
</section>
