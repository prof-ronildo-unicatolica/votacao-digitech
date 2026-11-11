{{-- Urna do terminal pareado, aguardando liberação (H7). Muda sozinha quando o mesário libera. --}}
@extends('layouts.urna')

@section('titulo', 'Urna · terminal '.$terminal->numero)
@section('topo-direita', 'Terminal '.$terminal->numero)

@section('conteudo')
    <div class="dt-urna-espera" role="status" aria-live="polite">
        <img src="{{ asset('img/marca/unicatolica-simbolo-laranja.png') }}" alt="">
        <h1>Aguardando liberação</h1>
        <p><span class="dt-urna-pulso" aria-hidden="true"></span>Apresente seu documento ao mesário.</p>
    </div>
    @include('urna._monitor')
@endsection
