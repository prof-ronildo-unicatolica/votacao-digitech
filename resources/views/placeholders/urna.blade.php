@extends('layouts.urna')

@section('titulo', 'Urna (terminal)')

@section('conteudo')
    <div class="dt-urna-cartao text-center">
        <h1>Urna (terminal)</h1>
        <p class="dt-urna-apoio">Tela ainda não implementada.</p>
        <x-alerta tipo="info" class="text-start" style="font-size: var(--dt-fonte-tamanho-lg)">
            Histórias <strong>H7 – Urna aguarda liberação</strong> e <strong>H8 – Votar</strong>
            (ver <code>docs/HISTORIAS_USUARIO.md</code>).
        </x-alerta>
        @unless (app()->isProduction())
            <div class="mt-5"><x-botao variante="contorno" tamanho="grande" :href="route('estilo.tela', 'urna-escolha')" icone="eye">Ver tela de referência</x-botao></div>
        @endunless
    </div>
@endsection
