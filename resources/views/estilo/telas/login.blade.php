{{-- Tela de referência (dados fictícios) · Login de admin e mesário (H1). --}}
@extends('layouts.base')

@section('titulo', 'Entrar')

@section('corpo')
    <main class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background: linear-gradient(180deg, var(--dt-tema-superficie-escura) 0 38%, var(--dt-tema-superficie-pagina) 38%)">
        <div style="width: 100%; max-width: 420px">
            <div class="text-center mb-4"><x-marca tema="escuro" tamanho="grande" /></div>
            <x-cartao class="shadow">
                <h1 class="h3 mb-1">Entrar</h1>
                <p class="text-secondary mb-4">Acesso da comissão eleitoral e dos mesários.</p>
                <form>
                    <x-campo name="email" type="email" label="E-mail" autocomplete="username" placeholder="nome@unicatolicaquixada.edu.br" />
                    <x-campo name="password" type="password" label="Senha" autocomplete="current-password" />
                    <x-botao type="submit" tamanho="grande" class="w-100">Entrar</x-botao>
                </form>
            </x-cartao>
            <p class="text-center small text-secondary mt-4">Eleitor não faz login: procure o mesário.</p>
        </div>
    </main>
@endsection
