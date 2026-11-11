{{--
    H10 · Ata da apuração, para imprimir ou salvar em PDF pelo navegador (D26). Página A4 em uma
    cor (logo preto), sem menu e sem JavaScript além do botão "Imprimir". Mesmos números da tela.
--}}
@extends('layouts.base')

@php
    $a = $resultado->apuracao;
    $eleicao = $resultado->eleicao;
    $vencedora = $resultado->vencedora();
    $formatar = fn (float $v) => number_format($v, 1, ',', '.').'%';
@endphp

@section('titulo', 'Ata · '.$eleicao->titulo)

@push('head')
    <style>
        body { background: #fff; color: #18181A; }
        .ata { max-width: 210mm; margin: 0 auto; padding: 16mm 14mm; font-size: 11pt; }
        .ata h1 { font-size: 16pt; margin: 0; }
        .ata h2 { font-size: 12pt; margin: 8mm 0 3mm; text-transform: uppercase; letter-spacing: .04em; }
        .ata table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
        .ata th, .ata td { border: 1px solid #9E9EA4; padding: 1.5mm 2.5mm; text-align: left; }
        .ata .num { text-align: right; }
        .ata dl { display: grid; grid-template-columns: max-content 1fr; gap: 1mm 6mm; margin: 0; }
        .ata dt { font-weight: 600; }
        .ata dd { margin: 0; }
        .ata .assinaturas { display: grid; grid-template-columns: 1fr 1fr; gap: 14mm 10mm; margin-top: 18mm; }
        .ata .assinaturas div { border-top: 1px solid #18181A; padding-top: 1.5mm; text-align: center; font-size: 9pt; }
        .ata .rodape { margin-top: 10mm; font-size: 8pt; color: #5c5c62; }
        @media print {
            .nao-imprimir { display: none !important; }
            .ata { padding: 0; }
            @page { size: A4; margin: 14mm; }
        }
    </style>
@endpush

@section('corpo')
    <div class="nao-imprimir d-flex justify-content-center gap-2 p-3 border-bottom">
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir ou salvar em PDF</button>
        <a class="btn btn-contorno" href="{{ route('admin.resultado', ['eleicao' => $eleicao->id]) }}">Voltar ao resultado</a>
    </div>

    <main class="ata">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <div class="small text-uppercase">Comissão eleitoral · Digitech</div>
                <h1>Ata de apuração · {{ $eleicao->titulo }}</h1>
            </div>
            <img src="{{ asset('img/marca/unicatolica-logo-preto.png') }}" alt="UniCatólica" style="height: 14mm">
        </div>

        <dl>
            <dt>Votação</dt><dd>de {{ $eleicao->votacao_abre_em->format('d/m/Y H:i') }} a {{ $eleicao->votacao_fecha_em->format('d/m/Y H:i') }}</dd>
            <dt>Eleitores aptos</dt><dd>{{ $a->aptos }}</dd>
            <dt>Votaram</dt><dd>{{ $a->votaram }} ({{ $formatar($a->participacao()) }} de participação)</dd>
            <dt>Votos apurados</dt><dd>{{ $a->total }}, sendo {{ $a->validos }} válidos, {{ $a->brancos }} {{ $a->brancos === 1 ? 'branco' : 'brancos' }} e {{ $a->nulos }} {{ $a->nulos === 1 ? 'nulo' : 'nulos' }}</dd>
            <dt>Conferência</dt><dd>{{ $a->conferida() ? 'votos apurados iguais ao número de eleitores que votaram' : 'DIVERGÊNCIA: '.$a->total.' votos para '.$a->votaram.' eleitores que votaram' }}</dd>
        </dl>

        <h2>Votos por opção</h2>
        <table>
            <thead><tr><th>Opção</th><th class="num">Votos</th><th class="num">% do total</th><th class="num">% dos válidos</th></tr></thead>
            <tbody>
                @foreach ($resultado->linhas() as $l)
                    <tr>
                        <td>{{ $l['rotulo'] }}</td>
                        <td class="num">{{ $l['votos'] }}</td>
                        <td class="num">{{ $formatar($l['total']) }}</td>
                        <td class="num">{{ $l['validos'] === null ? '—' : $formatar($l['validos']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><th>Total</th><th class="num">{{ $a->total }}</th><th></th><th></th></tr></tfoot>
        </table>

        <h2>Resultado</h2>
        @if ($vencedora)
            <p>Eleita a <strong>chapa {{ $vencedora->numero }} · {{ $vencedora->nome }}</strong>, com {{ $a->votosPorChapa[$vencedora->id] }} votos ({{ $formatar($a->percentuaisDosValidos()[$vencedora->id]) }} dos válidos), com a seguinte composição:</p>
            <table>
                <thead><tr><th>Cargo</th><th>Eleito</th><th>Matrícula</th></tr></thead>
                <tbody>
                    @foreach ($resultado->eleitos as $c)
                        <tr><td>{{ $c->cargo->titulo }}</td><td>{{ $c->eleitor->nome }}</td><td>{{ $c->eleitor->matricula }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @elseif ($a->empate())
            <p>Empate no primeiro lugar entre as chapas {{ $resultado->empatadas()->map(fn ($c) => $c->numero.' · '.$c->nome)->join(', ', ' e ') }}, com {{ $a->votosPorChapa[$a->primeiras[0]] }} votos cada. O desempate segue o regimento da eleição:</p>
            <p style="border-bottom: 1px solid #9E9EA4; height: 16mm"></p>
        @else
            <p>Nenhuma chapa recebeu votos válidos.</p>
        @endif

        <div class="assinaturas">
            <div>Presidente da comissão eleitoral</div>
            <div>Membro da comissão eleitoral</div>
            <div>Membro da comissão eleitoral</div>
            <div>Representante das chapas</div>
        </div>

        <p class="rodape">Emitida pelo sistema de votação em {{ $emitida->format('d/m/Y \à\s H:i') }}. Números gerados a partir dos votos registrados. O voto é secreto: nenhum registro liga o eleitor à sua escolha.</p>
    </main>
@endsection
