@extends('layouts.app')

@section('titulo', 'Painel do Mesário')

@section('conteudo')
    <div class="dt-cabecalho-pagina"><h1>Painel do Mesário</h1></div>
    <x-cartao>
        <x-estado-vazio icone="tools" titulo="Tela ainda não implementada">
            Histórias <strong>H6 – Liberar votação de um eleitor</strong> e <strong>H9 – Acompanhar a mesa</strong>
            (ver <code>docs/HISTORIAS_USUARIO.md</code>).
            @unless (app()->isProduction())
                <div class="mt-4"><x-botao variante="contorno" :href="route('estilo.tela', 'mesario')" icone="eye">Ver tela de referência</x-botao></div>
            @endunless
        </x-estado-vazio>
    </x-cartao>
@endsection
