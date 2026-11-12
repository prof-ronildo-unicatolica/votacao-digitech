{{--
    H10 · Resultado. Antes do fim da votação: só comparecimento (D26). Depois: tabela (sempre,
    ao lado do gráfico), gráfico de barras, vencedora com os eleitos por cargo e participação.
    Cores pela ordem do número da chapa, nunca pela colocação (docs/IDENTIDADE_VISUAL.md).
--}}
@extends('layouts.painel')

@php $formatar = fn (float $v) => number_format($v, 1, ',', '.').'%'; @endphp

@section('titulo', 'Resultado')
@section('sobretitulo', $eleicao?->titulo ?? 'Nenhuma eleição')
@section('menu', 'resultado')
@section('acoes')
    @if ($resultado)
        <x-botao variante="contorno" icone="filetype-csv" :href="route('admin.resultado.csv', $eleicao)">Exportar CSV</x-botao>
        <x-botao variante="secundario" icone="file-earmark-pdf" :href="route('admin.resultado.ata', $eleicao)" target="_blank">Ata para imprimir ou PDF</x-botao>
    @endif
@endsection

@section('conteudo')
    @if (! $eleicao)
        <x-estado-vazio icone="bar-chart" titulo="Nenhuma eleição cadastrada">
            Cadastre a eleição em <a href="{{ route('admin.eleicoes.index') }}">Eleições</a>. O resultado aparece aqui depois que a votação encerrar.
        </x-estado-vazio>
    @else
        @if ($eleicoes->count() > 1)
            <form method="GET" action="{{ route('admin.resultado') }}" class="d-flex align-items-end gap-2 mb-4" style="max-width: 32rem">
                <div class="flex-grow-1">
                    <label for="eleicao" class="form-label">Eleição</label>
                    <select id="eleicao" name="eleicao" class="form-select">
                        @foreach ($eleicoes as $e)
                            <option value="{{ $e->id }}" @selected($e->id === $eleicao->id)>{{ $e->titulo }}{{ $e->ativa ? ' (ativa)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <x-botao variante="contorno" type="submit">Ver</x-botao>
            </form>
        @endif

        @php $fase = $eleicao->fase(); @endphp
        <div class="d-flex align-items-center gap-2 mb-4">
            @if ($resultado)
                <x-selo icone="lock">Encerrada em {{ $eleicao->votacao_fecha_em->format('d/m/Y \à\s H:i') }}</x-selo>
            @elseif ($fase)
                <x-selo :tipo="$fase->selo()" :icone="$fase->icone()">{{ $fase->rotulo() }}</x-selo>
            @endif
        </div>

        @if (! $resultado)
            {{-- Antes do fim: nenhum número por chapa, nem na tela nem no HTML. --}}
            <x-alerta tipo="info" titulo="O resultado sai quando a votação encerrar" class="mb-4">
                @if ($eleicao->votacao_fecha_em)
                    A apuração abre aqui em {{ $eleicao->votacao_fecha_em->format('d/m/Y \à\s H:i') }}. Até lá, só o comparecimento é mostrado, para nenhuma parcial influenciar quem ainda vai votar.
                @else
                    Esta eleição ainda não tem as datas de votação. Cadastre-as em <a href="{{ route('admin.eleicoes.edit', $eleicao) }}">Eleições</a>.
                @endif
            </x-alerta>
            <div class="row g-3">
                <div class="col-sm-6 col-xl-4">
                    <x-indicador rotulo="Comparecimento" :valor="$comparecimento['votaram']"
                        :detalhe="'de '.$comparecimento['aptos'].' aptos · '.$formatar(\App\Support\Apuracao::percentual($comparecimento['votaram'], $comparecimento['aptos']))"
                        :progresso="\App\Support\Apuracao::percentual($comparecimento['votaram'], $comparecimento['aptos'])" />
                </div>
            </div>
        @else
            @php
                $a = $resultado->apuracao;
                $linhas = $resultado->linhas();
                $vencedora = $resultado->vencedora();
            @endphp

            @unless ($a->conferida())
                <x-alerta tipo="aviso" titulo="Total de votos diferente do comparecimento" class="mb-4">
                    Há {{ $a->total }} {{ $a->total === 1 ? 'voto' : 'votos' }} na apuração e {{ $a->votaram }} {{ $a->votaram === 1 ? 'eleitor votou' : 'eleitores votaram' }}. Confira a auditoria antes de assinar a ata.
                </x-alerta>
            @endunless

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3"><x-indicador rotulo="Votos registrados" :valor="$a->total" :detalhe="$a->validos.' válidos (em chapa)'" /></div>
                <div class="col-sm-6 col-xl-3"><x-indicador rotulo="Participação" :valor="$formatar($a->participacao())" :detalhe="$a->votaram.' de '.$a->aptos.' aptos'" :progresso="$a->participacao()" /></div>
                <div class="col-sm-6 col-xl-3"><x-indicador rotulo="Brancos e nulos" :valor="$a->brancos + $a->nulos" :detalhe="$a->brancos.($a->brancos === 1 ? ' branco' : ' brancos').' · '.$a->nulos.($a->nulos === 1 ? ' nulo' : ' nulos')" /></div>
                <div class="col-sm-6 col-xl-3">
                    <div class="dt-cartao dt-indicador h-100">
                        <div class="dt-indicador-rotulo">{{ $a->empate() ? 'Empate' : 'Chapa vencedora' }}</div>
                        <div class="dt-indicador-valor" style="font-size: var(--dt-fonte-tamanho-xl)">
                            @if ($vencedora)
                                {{ $vencedora->numero }} · {{ $vencedora->nome }}
                            @elseif ($a->empate())
                                {{ $resultado->empatadas()->map(fn ($c) => $c->numero)->join(', ', ' e ') }}
                            @else
                                —
                            @endif
                        </div>
                        <div class="dt-indicador-detalhe">
                            @if ($vencedora)
                                {{ $formatar($a->percentuaisDosValidos()[$vencedora->id]) }} dos votos válidos
                            @elseif ($a->empate())
                                {{ $a->primeiras ? $a->votosPorChapa[$a->primeiras[0]] : 0 }} votos cada. O desempate segue o regimento.
                            @else
                                Nenhum voto em chapa.
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($a->empate())
                <x-alerta tipo="aviso" titulo="Empate no primeiro lugar" class="mb-4">
                    O sistema não escolhe uma vencedora. A comissão aplica o critério de desempate do regimento e registra a decisão na ata.
                </x-alerta>
            @endif

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <x-cartao titulo="Votos por opção" sem-padding class="h-100">
                        <div class="table-responsive">
                            <table class="table dt-tabela">
                                <thead><tr><th>Opção</th><th class="text-end">Votos</th><th class="text-end">% do total</th><th class="text-end">% dos válidos</th></tr></thead>
                                <tbody>
                                    @foreach ($linhas as $l)
                                        <tr>
                                            <td><span class="dt-amostra-serie" style="background: {{ $l['cor'] }}"></span>{{ $l['rotulo'] }}</td>
                                            <td class="text-end">{{ $l['votos'] }}</td>
                                            <td class="text-end">{{ $formatar($l['total']) }}</td>
                                            <td class="text-end">{{ $l['validos'] === null ? '—' : $formatar($l['validos']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot><tr><th>Total</th><th class="text-end">{{ $a->total }}</th><th class="text-end">{{ $a->total ? '100%' : '—' }}</th><th class="text-end">{{ $a->validos ? '100%' : '—' }}</th></tr></tfoot>
                            </table>
                        </div>
                    </x-cartao>
                </div>
                <div class="col-xl-6">
                    <x-cartao titulo="Distribuição" class="h-100">
                        <div style="position: relative; height: {{ max(220, 44 * count($resultado->grafico())) }}px">
                            <canvas id="grafico-resultado" role="img" aria-label="Gráfico de barras com os votos por opção. Os mesmos números estão na tabela."></canvas>
                        </div>
                    </x-cartao>
                </div>
            </div>

            <x-cartao :titulo="$vencedora ? 'Eleitos · chapa '.$vencedora->numero.' · '.$vencedora->nome : 'Eleitos'" sem-padding>
                @if ($vencedora)
                    <table class="table dt-tabela">
                        <thead><tr><th>Cargo</th><th>Eleito</th><th>Turma</th></tr></thead>
                        <tbody>
                            @foreach ($resultado->eleitos as $c)
                                <tr><td>{{ $c->cargo->titulo }}</td><td>{{ $c->eleitor->nome }}</td><td>{{ $c->eleitor->turma ?? '—' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-estado-vazio icone="people" titulo="{{ $a->empate() ? 'Aguardando o desempate' : 'Nenhuma chapa eleita' }}">
                        {{ $a->empate() ? 'Os eleitos saem da chapa escolhida pelo critério de desempate do regimento.' : 'Nenhuma chapa recebeu votos válidos.' }}
                    </x-estado-vazio>
                @endif
            </x-cartao>
        @endif
    @endif
@endsection

@if ($resultado)
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            (() => {
                const barras = @json($resultado->grafico());
                const css = getComputedStyle(document.documentElement);
                const token = (nome) => css.getPropertyValue(nome).trim();
                Chart.defaults.font.family = token('--dt-fonte-familia-texto');
                Chart.defaults.color = token('--dt-cor-grafite-600');

                new Chart(document.getElementById('grafico-resultado'), {
                    type: 'bar',
                    data: {
                        // "10 · Nome" em duas linhas, para o rótulo caber no celular.
                        labels: barras.map((b) => b.rotulo.split(' · ')),
                        datasets: [{ data: barras.map((b) => b.votos), backgroundColor: barras.map((b) => b.cor), borderRadius: 4, borderSkipped: 'start', barThickness: 28 }],
                    },
                    options: {
                        indexAxis: 'y',
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: token('--dt-cor-grafite-100') }, border: { display: false } },
                            y: { grid: { display: false }, border: { color: token('--dt-cor-grafite-300') } },
                        },
                    },
                });
            })();
        </script>
    @endpush
@endif
