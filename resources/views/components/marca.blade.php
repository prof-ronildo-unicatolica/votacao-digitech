{{-- Assinatura do produto: símbolo da UniCatólica + "Votação DigiTech". Uso: <x-marca tema="escuro" /> --}}
@props(['tema' => 'claro', 'tamanho' => 'normal', 'href' => null])

@php($tag = $href ? 'a' : 'span')
<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['dt-marca', 'dt-marca--escura' => $tema === 'escuro', 'dt-marca--grande' => $tamanho === 'grande']) }}>
    <img src="{{ asset('img/marca/unicatolica-simbolo-laranja.png') }}" alt="" width="36" height="36">
    <span class="dt-marca-nome">
        Votação <strong>DigiTech</strong>
        <span class="dt-marca-instituicao">UniCatólica · Quixadá</span>
    </span>
</{{ $tag }}>
