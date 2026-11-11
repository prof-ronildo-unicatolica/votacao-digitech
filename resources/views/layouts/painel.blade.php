{{--
    Layout do admin: menu lateral escuro + conteúdo. Seções: titulo, sobretitulo, acoes, conteudo.
    Marque o item ativo com @section('menu', 'eleicoes').
    Itens com rota null ainda não foram implementados (aparecem desabilitados, com a história).
--}}
@extends('layouts.base')

@php
    $menu = [
        'Eleição' => [
            'painel' => ['Início', 'house', 'admin.painel', null],
            'eleicoes' => ['Eleições', 'calendar-check', 'admin.eleicoes.index', null],
            'cargos' => ['Cargos', 'list-ol', null, 'H13'],
            'chapas' => ['Chapas', 'people', null, 'H3'],
            'eleitores' => ['Eleitores', 'person-vcard', 'admin.eleitores.index', null],
            'terminais' => ['Terminais', 'display', null, 'H5'],
        ],
        'Acompanhamento' => [
            'mesa' => ['Mesa', 'person-check', 'mesario', null],
            'resultado' => ['Resultado', 'bar-chart', null, 'H10'],
            'auditoria' => ['Auditoria', 'shield-check', null, 'H11'],
        ],
        'Sistema' => [
            'usuarios' => ['Usuários', 'person-gear', 'admin.usuarios.index', null],
        ],
    ];
    $ativo = trim($__env->yieldContent('menu'));
@endphp

@section('corpo')
    <div class="dt-painel">
        <aside class="dt-menu-lateral">
            <x-marca tema="escuro" :href="auth()->check() ? route('admin.painel') : route('home')" />
            <nav aria-label="Administração">
                @foreach ($menu as $grupo => $itens)
                    <div class="dt-menu-titulo">{{ $grupo }}</div>
                    @foreach ($itens as $chave => [$rotulo, $icone, $rota, $historia])
                        @if ($rota)
                            <a href="{{ route($rota) }}" @if ($ativo === $chave) aria-current="page" @endif>
                                <i class="bi bi-{{ $icone }}" aria-hidden="true"></i>{{ $rotulo }}
                            </a>
                        @else
                            <a aria-disabled="true" class="dt-menu-em-breve" title="Ainda não implementado ({{ $historia }})">
                                <i class="bi bi-{{ $icone }}" aria-hidden="true"></i>{{ $rotulo }}<span class="ms-auto small">{{ $historia }}</span>
                            </a>
                        @endif
                    @endforeach
                @endforeach
            </nav>
            @auth
                <div class="dt-menu-usuario">
                    <div class="small">{{ auth()->user()->name }}</div>
                    <div class="small opacity-75 mb-2">{{ auth()->user()->perfil->rotulo() }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sair</button>
                    </form>
                </div>
            @endauth
        </aside>
        <main class="dt-painel-conteudo">
            <div class="dt-cabecalho-pagina">
                <div>
                    @hasSection('sobretitulo')<div class="dt-sobretitulo">@yield('sobretitulo')</div>@endif
                    <h1>@yield('titulo')</h1>
                </div>
                <div class="d-flex gap-2">@yield('acoes')</div>
            </div>
            @if (session('status'))
                <x-alerta tipo="sucesso" class="mb-4">{{ session('status') }}</x-alerta>
            @endif
            @error('geral')
                <x-alerta tipo="perigo" class="mb-4">{{ $message }}</x-alerta>
            @enderror
            @yield('conteudo')
        </main>
    </div>
@endsection
