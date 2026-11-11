{{-- Voto registrado (H8). Nunca mostra em quem votou. Volta para a espera em 5 segundos. --}}
@extends('layouts.urna')

@section('titulo', 'Urna · voto registrado')
@section('topo-direita', 'Terminal '.$terminal->numero)

@push('head')
    <meta http-equiv="refresh" content="5;url={{ route('urna') }}">
@endpush

@section('conteudo')
    <div class="dt-urna-cartao dt-urna-confirmado" role="status" aria-live="assertive">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <h1 class="mt-3">Voto registrado</h1>
        <p class="dt-urna-apoio mb-0">Obrigado por participar. Esta tela volta ao início em 5 segundos.</p>
    </div>
@endsection
