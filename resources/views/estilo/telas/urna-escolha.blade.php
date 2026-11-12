{{-- Tela de referência (dados fictícios) · Urna, escolha da chapa (H7/H8). --}}
@extends('layouts.urna')

@section('titulo', 'Urna · escolha')
@section('topo-direita', 'Terminal 3 · Eleição do Colegiado 2026.2')

@section('conteudo')
    <form class="dt-urna-cartao" action="{{ route('estilo.tela', 'urna-confirmacao') }}">
        <h1>Olá, Maria Oliveira</h1>
        <p class="dt-urna-apoio">Escolha uma opção e toque em <strong>Continuar</strong>.</p>

        <fieldset class="dt-opcoes">
            <legend class="visually-hidden">Opções de voto</legend>
            <x-opcao-chapa numero="10" nome="Inovação Acadêmica" descricao="Presidente: Ana Lima · Vice: Pedro Souza" value="10" />
            <x-opcao-chapa numero="20" nome="Movimento Estudantil" descricao="Presidente: Carla Dias · Vice: Rafael Nunes" value="20" />
            <x-opcao-chapa numero="30" nome="Integração e Pesquisa" descricao="Presidente: Lucas Rocha · Vice: Júlia Alves" value="30" />
            <x-opcao-chapa nome="Branco" descricao="Não escolher nenhuma chapa" value="branco" :neutra="true" />
        </fieldset>

        <div class="dt-urna-acoes">
            <x-botao type="submit" tamanho="grande">Continuar <i class="bi bi-arrow-right" aria-hidden="true"></i></x-botao>
        </div>
    </form>
@endsection
