@extends('layouts.painel')

@php
    $editando = $eleicao->exists;
    $travada = $editando && $eleicao->temInscricoes();
    $valor = fn (string $campo) => $eleicao->{$campo}?->format('Y-m-d\TH:i');
@endphp

@section('titulo', $editando ? 'Editar eleição' : 'Nova eleição')
@section('sobretitulo', 'Eleições')
@section('menu', 'eleicoes')

@section('conteudo')
    <form method="POST" action="{{ $editando ? route('admin.eleicoes.update', $eleicao) : route('admin.eleicoes.store') }}" novalidate style="max-width: 760px">
        @csrf
        @if ($editando) @method('PUT') @endif

        <x-cartao titulo="Identificação" class="mb-4">
            <x-campo name="titulo" label="Título" :value="$eleicao->titulo" required maxlength="150" autofocus
                placeholder="Ex.: Centro Acadêmico de Computação 2026.2" />
        </x-cartao>

        <x-cartao titulo="Período de inscrição de chapas" subtitulo="As chapas se inscrevem neste intervalo (formulário ou CSV)." class="mb-4">
            @if ($travada)
                <x-alerta tipo="info" class="mb-4">Já há chapas inscritas: a abertura não muda e o fim das inscrições só pode ser <strong>prorrogado</strong>.</x-alerta>
            @endif
            <div class="row">
                <div class="col-md-6"><x-campo type="datetime-local" name="inscricao_abre_em" label="Abertura" :value="$valor('inscricao_abre_em')" required :readonly="$travada" /></div>
                <div class="col-md-6"><x-campo type="datetime-local" name="inscricao_fecha_em" label="Fim" :value="$valor('inscricao_fecha_em')" required /></div>
            </div>
        </x-cartao>

        <x-cartao titulo="Período de votação" subtitulo="Começa no fim das inscrições ou depois (o intervalo entre os dois é a homologação)." class="mb-4">
            <div class="row">
                <div class="col-md-6"><x-campo type="datetime-local" name="votacao_abre_em" label="Início" :value="$valor('votacao_abre_em')" required /></div>
                <div class="col-md-6"><x-campo type="datetime-local" name="votacao_fecha_em" label="Fim" :value="$valor('votacao_fecha_em')" required /></div>
            </div>
        </x-cartao>

        <div class="d-flex gap-2">
            <x-botao type="submit" icone="check-lg">{{ $editando ? 'Salvar alterações' : 'Cadastrar eleição' }}</x-botao>
            <x-botao variante="contorno" :href="route('admin.eleicoes.index')">Cancelar</x-botao>
        </div>
    </form>
@endsection
