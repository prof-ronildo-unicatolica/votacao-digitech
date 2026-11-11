{{-- Inscrição fora do período, sem eleição ativa ou sem cargos (H14): mostra o motivo e as datas, sem formulário. --}}
@extends('layouts.app')

@section('titulo', 'Inscrição de chapa')

@section('conteudo')
    <div style="max-width: 640px" class="mx-auto">
        <div class="dt-cabecalho-pagina">
            <div>
                @if ($eleicao)<div class="dt-sobretitulo">{{ $eleicao->titulo }}</div>@endif
                <h1>Inscrição de chapa</h1>
            </div>
        </div>
        <x-cartao>
            <x-estado-vazio icone="calendar-x" titulo="Inscrições fechadas">{{ $mensagem }}</x-estado-vazio>
        </x-cartao>
    </div>
@endsection
