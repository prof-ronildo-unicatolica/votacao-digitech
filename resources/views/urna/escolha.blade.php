{{--
    Cédula (H7): o eleitor liberado vê o próprio nome e as chapas HOMOLOGADAS (D20), na ordem do
    número, todas com o mesmo estilo (neutralidade), cada uma com a composição na ordem dos cargos.
    Escolher, confirmar e registrar o voto é a H8.
--}}
@extends('layouts.urna')

@section('titulo', 'Urna · terminal '.$terminal->numero)
@section('topo-direita', 'Terminal '.$terminal->numero.' · '.$eleicao->titulo)

@section('conteudo')
    <form class="dt-urna-cartao" onsubmit="return false" novalidate>
        <h1>Olá, {{ $sessao->eleitor->nome }}</h1>
        <p class="dt-urna-apoio">Escolha uma opção. Você tem até as {{ $sessao->expira_em->format('H:i') }}.</p>

        <fieldset class="dt-opcoes">
            <legend class="visually-hidden">Opções de voto</legend>
            @foreach ($chapas as $chapa)
                <x-opcao-chapa :numero="$chapa->numero" :nome="$chapa->nome" :value="$chapa->id"
                    :descricao="$chapa->composicao->map(fn ($c) => $c->cargo->titulo.': '.$c->eleitor->nome)->implode(' · ')" />
            @endforeach
            <x-opcao-chapa nome="Branco" descricao="Não escolher nenhuma chapa" value="branco" :neutra="true" />
            <x-opcao-chapa nome="Nulo" descricao="Anular o voto" value="nulo" :neutra="true" />
        </fieldset>

        <div class="dt-urna-acoes">
            {{-- H8: o botão leva à confirmação e ao registro (POST /urna/votar). --}}
            <x-botao type="submit" tamanho="grande" disabled>Continuar <i class="bi bi-arrow-right" aria-hidden="true"></i></x-botao>
        </div>
        <p class="dt-urna-apoio mt-3 mb-0 small">Registro do voto: em construção (H8).</p>
    </form>
    @include('urna._monitor')
@endsection
