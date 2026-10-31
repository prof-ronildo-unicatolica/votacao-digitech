{{--
    Tela de login de admin e mesário (H1). Visual pronto; a AUTENTICAÇÃO é a história H1:
    criar POST /login (Auth::attempt + throttle), redirecionar por perfil e trocar o aviso abaixo.
--}}
@extends('layouts.base')

@section('titulo', 'Entrar')

@section('corpo')
    <main class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background: linear-gradient(180deg, var(--dt-tema-superficie-escura) 0 38%, var(--dt-tema-superficie-pagina) 38%)">
        <div style="width: 100%; max-width: 420px">
            <div class="text-center mb-4"><x-marca tema="escuro" tamanho="grande" :href="route('home')" /></div>
            <x-cartao class="shadow">
                <h1 class="h3 mb-1">Entrar</h1>
                <p class="text-secondary mb-4">Acesso da comissão eleitoral e dos mesários.</p>

                @if ($errors->any())
                    <x-alerta tipo="perigo" class="mb-4">{{ $errors->first() }}</x-alerta>
                @endif

                <form method="POST" action="#" novalidate>
                    @csrf
                    <x-campo name="email" type="email" label="E-mail" autocomplete="username" autofocus required placeholder="nome@unicatolicaquixada.edu.br" />
                    <x-campo name="password" type="password" label="Senha" autocomplete="current-password" required />
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="lembrar">
                        <label class="form-check-label" for="lembrar">Manter conectado neste computador</label>
                    </div>
                    <x-botao type="submit" tamanho="grande" class="w-100">Entrar</x-botao>
                </form>

                <x-alerta tipo="info" class="mt-4">A autenticação ainda não foi implementada (história <strong>H1</strong>).</x-alerta>
            </x-cartao>
            <p class="text-center small text-secondary mt-4">Eleitor não faz login: procure o mesário.</p>
        </div>
    </main>
@endsection
