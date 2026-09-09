@extends('layouts.app')

@section('conteudo')
    <h1 class="h3 mb-3">Scaffold funcionando <span class="badge bg-success">ok</span></h1>

    <div class="row g-3 mb-4">
        @foreach ($resumo as $rotulo => $valor)
            <div class="col-6 col-md-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted small">{{ $rotulo }}</div>
                    <div class="fs-5">{{ $valor }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    @if ($eleicao)
        <h2 class="h5">{{ $eleicao->titulo }}
            <span class="badge {{ $eleicao->votacaoAberta() ? 'bg-primary' : 'bg-secondary' }}">
                {{ $eleicao->votacaoAberta() ? 'votação aberta' : 'fora do período' }}
            </span>
        </h2>
        <p class="text-muted">{{ $eleicao->inicio->format('d/m/Y H:i') }} a {{ $eleicao->fim->format('d/m/Y H:i') }}</p>
        <ul class="list-group mb-4">
            @forelse ($chapas as $chapa)
                <li class="list-group-item d-flex justify-content-between">
                    <span><strong>{{ $chapa->numero }}</strong> — {{ $chapa->nome }}</span>
                    <span class="text-muted">{{ $chapa->descricao }}</span>
                </li>
            @empty
                <li class="list-group-item">Nenhuma chapa cadastrada.</li>
            @endforelse
        </ul>
    @else
        <div class="alert alert-warning">Nenhuma eleição ativa. Rode <code>php artisan db:seed</code>.</div>
    @endif

    <p class="small text-muted">Endpoints: <code>/api/health</code> · <code>/api/terminais/1/status</code></p>
@endsection
