{{--
    Voto registrado (H8). Nunca mostra em quem votou. Volta para a espera em 5 segundos, com a
    contagem na tela. O <meta refresh> volta mesmo sem JavaScript; o script só conta e, no zero,
    volta na hora (sem esperar o meta).
--}}
@extends('layouts.urna')

@php($segundos = 5)

@section('titulo', 'Urna · voto registrado')
@section('topo-direita', 'Terminal '.$terminal->numero)

@push('head')
    <meta http-equiv="refresh" content="{{ $segundos }};url={{ route('urna') }}">
@endpush

@section('conteudo')
    <div class="dt-urna-cartao dt-urna-confirmado" role="status" aria-live="assertive">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <h1 class="mt-3">Voto registrado</h1>
        <p class="dt-urna-apoio mb-0">
            Obrigado por participar. Esta tela volta ao início em
            {{-- O número muda a cada segundo só para quem vê; o leitor de tela ouve a frase uma vez. --}}
            <span aria-hidden="true"><strong data-contagem="{{ $segundos }}">{{ $segundos }}</strong> <span data-contagem-unidade>segundos</span></span><span class="visually-hidden">{{ $segundos }} segundos</span>.
        </p>
    </div>

    @push('scripts')
        <script>
            (() => {
                const numero = document.querySelector('[data-contagem]');
                const unidade = document.querySelector('[data-contagem-unidade]');
                let restam = Number(numero.dataset.contagem);
                const relogio = setInterval(() => {
                    restam -= 1;
                    if (restam <= 0) {
                        clearInterval(relogio);
                        return location.replace(@json(route('urna')));
                    }
                    numero.textContent = restam;
                    unidade.textContent = restam === 1 ? 'segundo' : 'segundos';
                }, 1000);
            })();
        </script>
    @endpush
@endsection
