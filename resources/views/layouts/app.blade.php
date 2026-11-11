{{-- Layout padrão: barra superior escura com a marca. Telas simples (início, login, erros). --}}
@extends('layouts.base')

@section('corpo')
    <header class="dt-topo mb-5">
        <nav class="container d-flex flex-wrap align-items-center justify-content-between gap-3 py-3" aria-label="Principal">
            <x-marca tema="escuro" :href="route('home')" />
            <div class="nav">
                <a @class(['nav-link', 'active' => request()->routeIs('mesario')]) href="{{ route('mesario') }}">Mesário</a>
                <a @class(['nav-link', 'active' => request()->routeIs('admin')]) href="{{ route('admin') }}">Admin</a>
                <a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Entrar</a>
                @unless (app()->isProduction())
                    <a @class(['nav-link', 'active' => request()->routeIs('estilo*')]) href="{{ route('estilo') }}">Estilo</a>
                @endunless
            </div>
        </nav>
    </header>
    <main class="container">
        @yield('conteudo')
    </main>
    <footer class="container dt-rodape mt-5 py-5">
        Laboratório DIGITECH — UniCatólica · ambiente: {{ app()->environment() }}
    </footer>
@endsection
