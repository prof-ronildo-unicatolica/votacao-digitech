{{-- Layout da urna em modo quiosque (H7, H8): fundo escuro, sem menu, fonte mínima de 24px. --}}
@extends('layouts.base')

@section('corpo')
    <div class="dt-urna">
        <header class="dt-urna-topo">
            <x-marca tema="escuro" />
            <span>@yield('topo-direita')</span>
        </header>
        <main class="dt-urna-palco">
            @yield('conteudo')
        </main>
    </div>
@endsection
