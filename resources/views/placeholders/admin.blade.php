@extends('layouts.painel')

@section('titulo', 'Administração')
@section('sobretitulo', 'Comissão eleitoral')

@section('conteudo')
    <x-cartao>
        <x-estado-vazio icone="tools" titulo="Tela ainda não implementada">
            Histórias <strong>H2 a H5</strong> (cadastrar eleição, chapas, eleitores e terminais); depois <strong>H10</strong> (resultado) e <strong>H11</strong> (auditoria).
            Ver <code>docs/HISTORIAS_USUARIO.md</code>.
            @unless (app()->isProduction())
                <div class="mt-4"><x-botao variante="contorno" :href="route('estilo.tela', 'apuracao')" icone="eye">Ver tela de referência</x-botao></div>
            @endunless
        </x-estado-vazio>
    </x-cartao>
@endsection
