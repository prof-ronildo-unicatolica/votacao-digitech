@extends('layouts.painel')

@php($editando = $terminal->exists)

@section('titulo', $editando ? "Editar terminal {$terminal->numero}" : 'Novo terminal')
@section('sobretitulo', 'Terminais')
@section('menu', 'terminais')

@section('conteudo')
    <form method="POST" action="{{ $editando ? route('admin.terminais.update', $terminal) : route('admin.terminais.store') }}" novalidate style="max-width: 560px">
        @csrf
        @if ($editando) @method('PUT') @endif

        <x-cartao class="mb-4">
            <x-campo name="numero" type="number" label="Número" :value="$terminal->numero" min="1" max="999" required autofocus
                ajuda="É o número que o mesário vê e escolhe ao liberar um eleitor." />
            <x-campo name="local" label="Local" :value="$terminal->local" autocomplete="off" ajuda="Opcional. Ex.: Laboratório 2, mesa da janela." />
        </x-cartao>

        <div class="d-flex gap-2">
            <x-botao type="submit" icone="check-lg">{{ $editando ? 'Salvar alterações' : 'Cadastrar terminal' }}</x-botao>
            <x-botao variante="contorno" :href="route('admin.terminais.index')">Cancelar</x-botao>
        </div>
    </form>
@endsection
