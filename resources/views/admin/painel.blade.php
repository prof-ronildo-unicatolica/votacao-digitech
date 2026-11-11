@extends('layouts.painel')

@section('titulo', 'Início')
@section('sobretitulo', 'Comissão eleitoral')
@section('menu', 'painel')

@section('conteudo')
    @if ($eleicao)
        @php($fase = $eleicao->fase())
        <x-cartao :titulo="$eleicao->titulo" subtitulo="Eleição ativa" class="mb-4">
            <x-slot:acoes>
                <x-botao variante="contorno" tamanho="pequeno" icone="pencil" :href="route('admin.eleicoes.edit', $eleicao)">Editar</x-botao>
            </x-slot:acoes>
            @if ($fase)
                <div class="mb-4"><x-selo :tipo="$fase->selo()" :icone="$fase->icone()">{{ $fase->rotulo() }}</x-selo></div>
            @endif
            @include('admin._linha-do-tempo', ['eleicao' => $eleicao])
        </x-cartao>

        <div class="row g-3">
            <div class="col-sm-6 col-xl-3"><a href="{{ route('admin.eleicoes.cargos.index', $eleicao) }}" class="text-reset text-decoration-none"><x-indicador rotulo="Cargos por chapa" :valor="$eleicao->cargos_count" /></a></div>
            <div class="col-sm-6 col-xl-3"><x-indicador rotulo="Chapas inscritas" :valor="$eleicao->chapas_count" /></div>
            <div class="col-sm-6 col-xl-3"><x-indicador rotulo="Eleitores cadastrados" :valor="$eleitores" /></div>
        </div>

        @if ($eleicao->cargos_count === 0)
            <x-alerta tipo="aviso" titulo="Sem cargos definidos" class="mt-4">
                As inscrições não podem abrir sem os cargos das chapas (Presidente, Vice...).
                <a href="{{ route('admin.eleicoes.cargos.index', $eleicao) }}">Definir os cargos</a>.
            </x-alerta>
        @endif

        @if ($eleitores === 0)
            <x-alerta tipo="aviso" titulo="Eleitorado vazio" class="mt-4">
                Importe os alunos antes de abrir as inscrições: os candidatos das chapas são indicados pela matrícula.
                <a href="{{ route('admin.eleitores.importar') }}">Importar eleitores</a>.
            </x-alerta>
        @endif
    @else
        <x-cartao>
            <x-estado-vazio icone="calendar-plus" titulo="Nenhuma eleição ativa">
                Cadastre uma eleição com os períodos de inscrição e de votação, ou ative uma já cadastrada.
                <div class="mt-4 d-flex gap-2 justify-content-center">
                    <x-botao icone="plus-lg" :href="route('admin.eleicoes.create')">Cadastrar eleição</x-botao>
                    <x-botao variante="contorno" :href="route('admin.eleicoes.index')">Ver eleições</x-botao>
                </div>
            </x-estado-vazio>
        </x-cartao>
    @endif
@endsection
