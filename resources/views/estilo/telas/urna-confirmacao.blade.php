{{-- Tela de referência (dados fictícios) · Urna, confirmação (H8). --}}
@extends('layouts.urna')

@section('titulo', 'Urna · confirmação')
@section('topo-direita', 'Terminal 3 · Eleição do Colegiado 2026.2')

@section('conteudo')
    <div class="dt-urna-cartao">
        <h1>Confirme seu voto</h1>
        <p class="dt-urna-apoio">Depois de confirmar, não é possível alterar.</p>

        <div class="dt-opcao" style="cursor: default; border-color: var(--dt-tema-acao-primaria); background: var(--dt-tema-superficie-destaque); box-shadow: 0 0 0 2px var(--dt-tema-acao-primaria)">
            <span class="dt-opcao-numero">20</span>
            <span>
                <span class="dt-opcao-nome">Movimento Estudantil</span>
                <span class="dt-opcao-descricao d-block">Presidente: Carla Dias · Vice: Rafael Nunes</span>
            </span>
            <span></span>
        </div>

        <div class="dt-urna-acoes">
            <x-botao variante="contorno" tamanho="grande" :href="route('estilo.tela', 'urna-escolha')"><i class="bi bi-arrow-left" aria-hidden="true"></i> Corrigir</x-botao>
            <x-botao tamanho="grande" :href="route('estilo.tela', 'urna-registrado')"><i class="bi bi-check-lg" aria-hidden="true"></i> Confirmar voto</x-botao>
        </div>
    </div>
@endsection
