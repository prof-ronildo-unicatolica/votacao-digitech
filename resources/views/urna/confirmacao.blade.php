{{--
    Confirmação do voto (H8). Mostra a escolha e pede confirmação. Nada foi gravado: a escolha
    existe só neste formulário (nem na sessão HTTP, D24). "Corrigir" volta para a cédula.
--}}
@extends('layouts.urna')

@section('titulo', 'Urna · confirmar')
@section('topo-direita', 'Terminal '.$terminal->numero.' · '.$sessao->eleicao->titulo)

@section('conteudo')
    <form class="dt-urna-cartao" method="POST" action="{{ route('urna.votar') }}" data-um-envio>
        @csrf
        <input type="hidden" name="versao" value="{{ $versao }}">
        <input type="hidden" name="escolha" value="{{ $escolha }}">

        <h1>Confirme seu voto</h1>
        <p class="dt-urna-apoio">Depois de confirmar, não é possível alterar.</p>

        <div class="dt-opcao dt-opcao--escolhida @if (! $chapa) dt-opcao--neutra @endif">
            <span class="dt-opcao-numero">{{ $chapa?->numero ?? '—' }}</span>
            <span>
                <span class="dt-opcao-nome">{{ $chapa?->nome ?? $tipo->rotulo() }}</span>
                @if ($chapa)
                    <span class="dt-opcao-descricao d-block">
                        @foreach ($chapa->composicao as $candidato)
                            {{ $candidato->cargo->titulo }}: {{ $candidato->eleitor->nome }}@if (! $loop->last)<br>@endif
                        @endforeach
                    </span>
                @else
                    <span class="dt-opcao-descricao d-block">{{ $tipo->value === 'branco' ? 'Você não escolheu nenhuma chapa.' : 'Você vai anular o voto.' }}</span>
                @endif
            </span>
            <span></span>
        </div>

        <div class="dt-urna-acoes">
            <x-botao variante="contorno" tamanho="grande" :href="route('urna')"><i class="bi bi-arrow-left" aria-hidden="true"></i> Corrigir</x-botao>
            <x-botao type="submit" tamanho="grande"><i class="bi bi-check-lg" aria-hidden="true"></i> Confirmar voto</x-botao>
        </div>
    </form>
    @include('urna._monitor')

    @push('scripts')
        <script>
            // Um envio só: o segundo clique não sai (o servidor também recusa um segundo voto).
            document.querySelector('[data-um-envio]').addEventListener('submit', (evento) => {
                const botao = evento.target.querySelector('button[type="submit"]');
                if (botao.disabled) return evento.preventDefault();
                botao.disabled = true;
            });
        </script>
    @endpush
@endsection
