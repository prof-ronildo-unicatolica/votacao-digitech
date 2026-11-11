{{-- Tela de referência (dados fictícios) · Urna, voto registrado (H8). Nunca mostra em quem votou. --}}
@extends('layouts.urna')

@section('titulo', 'Urna · voto registrado')
@section('topo-direita', 'Terminal 3')

@section('conteudo')
    <div class="dt-urna-cartao dt-urna-confirmado" role="status" aria-live="assertive">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <h1 class="mt-3">Voto registrado</h1>
        <p class="dt-urna-apoio mb-0">Obrigado por participar. Esta tela volta ao início em 5 segundos.</p>
    </div>
@endsection
