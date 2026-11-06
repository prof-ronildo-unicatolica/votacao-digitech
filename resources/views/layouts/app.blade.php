{{-- Layout padrão: barra superior escura com a marca. Telas simples (início, login, erros). --}}
@extends('layouts.base')

@section('corpo')
    <header class="dt-topo mb-5">
        <nav class="container d-flex flex-wrap align-items-center justify-content-between gap-3 py-3" aria-label="Principal">
            <x-marca tema="escuro" :href="route('home')" />
            <div class="nav">
                @auth
                    <a @class(['nav-link', 'active' => request()->routeIs('mesario')]) href="{{ route('mesario') }}">Mesário</a>
                    @if (auth()->user()->isAdmin())
                        <a @class(['nav-link', 'active' => request()->routeIs('admin.*')]) href="{{ route('admin.painel') }}">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="nav-link btn btn-link"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sair ({{ auth()->user()->name }})</button>
                    </form>
                @else
                    <a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Entrar</a>
                @endauth
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
