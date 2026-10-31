{{--
    Urna do terminal, aguardando liberação (H7). Visual pronto; a LÓGICA é a H7/H8:
    consultar /api/terminais/{id}/status a cada 3 s e, quando liberada, mostrar as chapas
    (componente x-opcao-chapa, ver /estilo/telas/urna-escolha).
--}}
@extends('layouts.urna')

@section('titulo', 'Urna · terminal '.$terminal->numero)
@section('topo-direita', 'Terminal '.$terminal->numero)

@section('conteudo')
    <div class="dt-urna-espera" role="status" aria-live="polite">
        <img src="{{ asset('img/marca/unicatolica-simbolo-laranja.png') }}" alt="">
        <h1>Aguardando liberação</h1>
        <p><span class="dt-urna-pulso" aria-hidden="true"></span>Apresente seu documento ao mesário.</p>
    </div>
@endsection
