{{--
    Layout do admin: menu lateral escuro + conteúdo. Seções: titulo, sobretitulo, acoes, conteudo.
    Marque o item ativo com @section('menu', 'eleicoes').
--}}
@extends('layouts.base')

@php
    $menu = [
        'Eleição' => [
            'eleicoes' => ['Eleições', 'calendar-check'],
            'chapas' => ['Chapas', 'people'],
            'eleitores' => ['Eleitores', 'person-vcard'],
            'terminais' => ['Terminais', 'display'],
        ],
        'Acompanhamento' => [
            'resultado' => ['Resultado', 'bar-chart'],
            'auditoria' => ['Auditoria', 'shield-check'],
        ],
    ];
    $ativo = trim($__env->yieldContent('menu'));
@endphp

@section('corpo')
    <div class="dt-painel">
        <aside class="dt-menu-lateral">
            <x-marca tema="escuro" :href="route('home')" />
            <nav aria-label="Administração">
                @foreach ($menu as $grupo => $itens)
                    <div class="dt-menu-titulo">{{ $grupo }}</div>
                    @foreach ($itens as $chave => [$rotulo, $icone])
                        <a href="#" @if ($ativo === $chave) aria-current="page" @endif>
                            <i class="bi bi-{{ $icone }}" aria-hidden="true"></i>{{ $rotulo }}
                        </a>
                    @endforeach
                @endforeach
            </nav>
        </aside>
        <main class="dt-painel-conteudo">
            <div class="dt-cabecalho-pagina">
                <div>
                    @hasSection('sobretitulo')<div class="dt-sobretitulo">@yield('sobretitulo')</div>@endif
                    <h1>@yield('titulo')</h1>
                </div>
                <div class="d-flex gap-2">@yield('acoes')</div>
            </div>
            @yield('conteudo')
        </main>
    </div>
@endsection
