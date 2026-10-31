{{-- Tela de referência (dados fictícios) · Urna aguardando liberação (H7). --}}
@extends('layouts.urna')

@section('titulo', 'Urna · aguardando')
@section('topo-direita', 'Terminal 3')

@section('conteudo')
    <div class="dt-urna-espera" role="status" aria-live="polite">
        <img src="{{ asset('img/marca/unicatolica-simbolo-laranja.png') }}" alt="">
        <h1>Aguardando liberação</h1>
        <p><span class="dt-urna-pulso" aria-hidden="true"></span>Apresente seu documento ao mesário.</p>
    </div>
@endsection
