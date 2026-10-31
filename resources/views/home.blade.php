@extends('layouts.app')

@section('titulo', 'Início')

@section('conteudo')
    <div class="dt-cabecalho-pagina">
        <div>
            <div class="dt-sobretitulo">Banco: <span class="dt-mono">{{ $banco }}</span></div>
            <h1>Scaffold funcionando <x-selo tipo="sucesso" icone="check-circle-fill">ok</x-selo></h1>
        </div>
    </div>

    <div class="row g-3 mb-6">
        @foreach ($contagens as $tabela => $total)
            <div class="col-6 col-md-4 col-xl-2">
                <x-indicador :rotulo="$tabela" :valor="$total" />
            </div>
        @endforeach
    </div>

    @forelse ($eleicoes as $eleicao)
        <x-cartao titulo="Eleição" :subtitulo="$eleicao->id" sem-padding class="mb-4">
            <ul class="list-group list-group-flush">
                @forelse ($eleicao->chapas as $chapa)
                    <li class="list-group-item px-4">Chapa <strong>{{ $chapa->numero }}</strong></li>
                @empty
                    <li class="list-group-item px-4 text-secondary">Nenhuma chapa cadastrada.</li>
                @endforelse
            </ul>
        </x-cartao>
    @empty
        <x-alerta tipo="aviso" titulo="Nenhuma eleição">Rode <code>php artisan db:seed</code>.</x-alerta>
    @endforelse

    <p class="small text-secondary mt-5">
        As tabelas têm só chaves e constraints; os atributos são definidos pela equipe (<code>docs/MODELO_DADOS.md</code>).
        Endpoints: <a href="/api/health"><code>/api/health</code></a>
        @if ($terminal)
            · <a href="{{ route('api.terminal.status', $terminal) }}"><code>/api/terminais/{uuid}/status</code> (terminal {{ $terminal->numero }})</a>
        @endif
    </p>
@endsection
