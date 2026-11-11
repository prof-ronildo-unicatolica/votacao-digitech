{{--
    Cédula (H7): o eleitor liberado vê o próprio nome e as chapas HOMOLOGADAS (D20), na ordem do
    número, todas com o mesmo estilo (neutralidade), cada uma com a composição na ordem dos cargos.
    Continuar leva à confirmação (H8), que só mostra a escolha; o voto é gravado depois.
--}}
@extends('layouts.urna')

@section('titulo', 'Urna · terminal '.$terminal->numero)
@section('topo-direita', 'Terminal '.$terminal->numero.' · '.$eleicao->titulo)

@section('conteudo')
    <form class="dt-urna-cartao" method="POST" action="{{ route('urna.confirmar') }}">
        @csrf
        <input type="hidden" name="versao" value="{{ $versao }}">
        <h1>Olá, {{ $sessao->eleitor->nome }}</h1>
        <p class="dt-urna-apoio">Escolha uma opção. Você tem até as {{ $sessao->expira_em->format('H:i') }}.</p>

        <fieldset class="dt-opcoes">
            <legend class="visually-hidden">Opções de voto</legend>
            @foreach ($chapas as $chapa)
                <x-opcao-chapa name="escolha" :numero="$chapa->numero" :nome="$chapa->nome" :value="$chapa->id"
                    :descricao="$chapa->composicao->map(fn ($c) => $c->cargo->titulo.': '.$c->eleitor->nome)->implode(' · ')" />
            @endforeach
            <x-opcao-chapa name="escolha" nome="Branco" descricao="Não escolher nenhuma chapa" value="branco" :neutra="true" />
            <x-opcao-chapa name="escolha" nome="Nulo" descricao="Anular o voto" value="nulo" :neutra="true" />
        </fieldset>

        <div class="dt-urna-acoes">
            <x-botao type="submit" tamanho="grande">Continuar <i class="bi bi-arrow-right" aria-hidden="true"></i></x-botao>
        </div>
    </form>
    @include('urna._monitor')
@endsection
