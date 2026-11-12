@extends('layouts.painel')

@php($editando = $usuario->exists)

@section('titulo', $editando ? 'Editar usuário' : 'Novo usuário')
@section('sobretitulo', 'Usuários')
@section('menu', 'usuarios')

@section('conteudo')
    <form method="POST" action="{{ $editando ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" novalidate style="max-width: 560px">
        @csrf
        @if ($editando) @method('PUT') @endif

        <x-cartao class="mb-4">
            <x-campo name="name" label="Nome" :value="$usuario->name" required autofocus autocomplete="off" />
            <x-campo name="email" type="email" label="E-mail" :value="$usuario->email" required autocomplete="off" />
            <x-campo name="matricula" label="Matrícula (se também for aluno)" :value="$usuario->matricula" autocomplete="off"
                ajuda="Para mesário ou admin que está no eleitorado. Com a matrícula, ele não pode ser inscrito como candidato (D19)." />

            @php($perfilAtual = old('perfil', $usuario->perfil?->value))
            <div class="mb-4">
                <label for="campo-perfil" class="form-label">Perfil</label>
                <select id="campo-perfil" name="perfil" @class(['form-select', 'form-select-lg', 'is-invalid' => $errors->has('perfil')])>
                    @foreach (\App\Enums\Perfil::cases() as $perfil)
                        <option value="{{ $perfil->value }}" @selected($perfilAtual === $perfil->value)>{{ $perfil->rotulo() }}</option>
                    @endforeach
                </select>
                @error('perfil')
                    <div class="invalid-feedback"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                @enderror
                <div class="form-text">Admin gerencia eleições, chapas e usuários. Mesário só libera eleitores.</div>
            </div>

            <x-campo name="password" type="password" label="Senha" autocomplete="new-password"
                :ajuda="$editando ? 'Deixe em branco para manter a senha atual. Mínimo de 8 caracteres.' : 'Mínimo de 8 caracteres.'" />
            <x-campo name="password_confirmation" type="password" label="Confirme a senha" autocomplete="new-password" />
        </x-cartao>

        <div class="d-flex gap-2">
            <x-botao type="submit" icone="check-lg">{{ $editando ? 'Salvar alterações' : 'Cadastrar usuário' }}</x-botao>
            <x-botao variante="contorno" :href="route('admin.usuarios.index')">Cancelar</x-botao>
        </div>
    </form>
@endsection
