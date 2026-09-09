@extends('layouts.app')

@section('conteudo')
    <h1 class="h3 mb-1">Scaffold funcionando <span class="badge bg-success">ok</span></h1>
    <p class="text-muted">Banco: {{ $banco }}</p>

    <div class="row g-3 mb-4">
        @foreach ($contagens as $tabela => $total)
            <div class="col-6 col-md-2">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted small"><code>{{ $tabela }}</code></div>
                    <div class="fs-4">{{ $total }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    @forelse ($eleicoes as $eleicao)
        <h2 class="h5">Eleição #{{ $eleicao->id }}</h2>
        <ul class="list-group mb-4">
            @forelse ($eleicao->chapas as $chapa)
                <li class="list-group-item">Chapa <strong>{{ $chapa->numero }}</strong></li>
            @empty
                <li class="list-group-item">Nenhuma chapa cadastrada.</li>
            @endforelse
        </ul>
    @empty
        <div class="alert alert-warning">Nenhuma eleição. Rode <code>php artisan db:seed</code>.</div>
    @endforelse

    <p class="small text-muted">
        As tabelas têm só chaves e constraints; os atributos são definidos pela equipe (<code>docs/MODELO_DADOS.md</code>).
        Endpoints: <code>/api/health</code> · <code>/api/terminais/1/status</code>
    </p>
@endsection
