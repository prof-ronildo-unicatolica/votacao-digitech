{{-- As quatro datas da eleição, na ordem (H2). Uso: @include('admin._linha-do-tempo', ['eleicao' => $eleicao]) --}}
@php
    $datas = [
        ['Inscrições abrem', $eleicao->inscricao_abre_em],
        ['Inscrições fecham', $eleicao->inscricao_fecha_em],
        ['Votação abre', $eleicao->votacao_abre_em],
        ['Votação fecha', $eleicao->votacao_fecha_em],
    ];
@endphp
<div class="row g-3">
    @foreach ($datas as [$rotulo, $data])
        <div class="col-6 col-lg-3">
            <div class="small text-secondary">{{ $rotulo }}</div>
            <div class="fw-semibold">{{ $data?->format('d/m/Y \à\s H:i') ?? '—' }}</div>
        </div>
    @endforeach
</div>
