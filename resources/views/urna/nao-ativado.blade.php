{{-- Urna aberta num computador sem pareamento válido (D16): 403. O mesário ativa em /terminal/ativar. --}}
@extends('layouts.urna')

@section('titulo', 'Terminal não ativado')

@section('conteudo')
    <div class="dt-urna-espera" role="alert">
        <img src="{{ asset('img/marca/unicatolica-simbolo-laranja.png') }}" alt="">
        <h1>Terminal não ativado</h1>
        <p>{{ $mensagem }}</p>
    </div>
@endsection
