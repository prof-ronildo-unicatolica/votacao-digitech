@extends('layouts.painel')

@php($editando = $eleitor->exists)

@section('titulo', $editando ? 'Editar eleitor' : 'Novo eleitor')
@section('sobretitulo', 'Eleitores')
@section('menu', 'eleitores')

@section('conteudo')
    <form method="POST" action="{{ $editando ? route('admin.eleitores.update', $eleitor) : route('admin.eleitores.store') }}" novalidate style="max-width: 560px">
        @csrf
        @if ($editando) @method('PUT') @endif

        <x-cartao class="mb-4">
            <x-campo name="matricula" label="Matrícula" :value="$eleitor->matricula" mono required autofocus autocomplete="off"
                ajuda="Letras, números, ponto e hífen. É por ela que o mesário encontra o eleitor e que a chapa indica os candidatos." />
            <x-campo name="nome" label="Nome" :value="$eleitor->nome" required autocomplete="off" />
            <x-campo name="turma" label="Turma" :value="$eleitor->turma" autocomplete="off" ajuda="Opcional. Ex.: ADS 3." />
            <x-campo name="email" type="email" label="E-mail" :value="$eleitor->email" autocomplete="off" ajuda="Opcional." />
        </x-cartao>

        <div class="d-flex gap-2">
            <x-botao type="submit" icone="check-lg">{{ $editando ? 'Salvar alterações' : 'Cadastrar eleitor' }}</x-botao>
            <x-botao variante="contorno" :href="route('admin.eleitores.index')">Cancelar</x-botao>
        </div>
    </form>
@endsection
