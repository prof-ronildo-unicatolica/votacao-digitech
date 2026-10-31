{{--
    Tela de referência (dados fictícios) · Resultado da eleição (H10).
    Barras horizontais (comparar quantidades) em vez de rosca; a tabela é obrigatória ao lado do gráfico.
    Cores pela ordem do número da chapa (tokens cor.dados.*), nunca pelo resultado.
--}}
@extends('layouts.painel')

@php
    $opcoes = [
        ['rotulo' => '10 · Inovação Acadêmica', 'votos' => 180, 'cor' => 'var(--dt-cor-dados-1)', 'hex' => '#2A78D6'],
        ['rotulo' => '20 · Movimento Estudantil', 'votos' => 245, 'cor' => 'var(--dt-cor-dados-2)', 'hex' => '#1BAF7A'],
        ['rotulo' => '30 · Integração e Pesquisa', 'votos' => 45, 'cor' => 'var(--dt-cor-dados-3)', 'hex' => '#6250D6'],
        ['rotulo' => 'Brancos', 'votos' => 30, 'cor' => 'var(--dt-cor-dados-branco-nulo)', 'hex' => '#9E9EA4'],
    ];
    $total = array_sum(array_column($opcoes, 'votos'));
    $pct = \App\Support\Apuracao::percentuais(array_column($opcoes, 'votos'));
@endphp

@section('titulo', 'Resultado')
@section('sobretitulo', 'Eleição do Colegiado 2026.2')
@section('menu', 'resultado')
@section('acoes')
    <x-botao variante="contorno" icone="filetype-csv">Exportar CSV</x-botao>
    <x-botao variante="secundario" icone="file-earmark-pdf">Ata em PDF</x-botao>
@endsection

@section('conteudo')
    <div class="d-flex align-items-center gap-2 mb-4">
        <x-selo icone="lock">Encerrada em 31/10/2026 às 18:00</x-selo>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><x-indicador rotulo="Votos registrados" valor="{{ $total }}" /></div>
        <div class="col-sm-6 col-xl-3"><x-indicador rotulo="Participação" valor="50%" detalhe="500 de 1.000 aptos" :progresso="50" /></div>
        <div class="col-sm-6 col-xl-3"><x-indicador rotulo="Brancos" valor="30" detalhe="6% do total" /></div>
        <div class="col-sm-6 col-xl-3">
            <div class="dt-cartao dt-indicador h-100">
                <div class="dt-indicador-rotulo">Mais votada</div>
                <div class="dt-indicador-valor" style="font-size: var(--dt-fonte-tamanho-xl)">
                    <span class="dt-amostra-serie" style="background: var(--dt-cor-dados-2)"></span>20 · Movimento Estudantil
                </div>
                <div class="dt-indicador-detalhe">52% dos votos válidos</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <x-cartao titulo="Votos por opção" sem-padding class="h-100">
                <table class="table dt-tabela">
                    <thead><tr><th>Opção</th><th class="text-end">Votos</th><th class="text-end">%</th></tr></thead>
                    <tbody>
                        @foreach ($opcoes as $i => $o)
                            <tr>
                                <td><span class="dt-amostra-serie" style="background: {{ $o['cor'] }}"></span>{{ $o['rotulo'] }}</td>
                                <td class="text-end">{{ $o['votos'] }}</td>
                                <td class="text-end">{{ number_format($pct[$i], 1, ',', '') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><th>Total</th><th class="text-end">{{ $total }}</th><th class="text-end">100%</th></tr></tfoot>
                </table>
            </x-cartao>
        </div>
        <div class="col-xl-6">
            <x-cartao titulo="Distribuição" class="h-100">
                <div style="position: relative; height: 260px">
                    <canvas id="grafico-resultado" role="img" aria-label="Gráfico de barras com os votos por opção; os mesmos números estão na tabela."></canvas>
                </div>
            </x-cartao>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        const css = getComputedStyle(document.documentElement);
        const token = (nome) => css.getPropertyValue(nome).trim();
        Chart.defaults.font.family = token('--dt-fonte-familia-texto');
        Chart.defaults.color = token('--dt-cor-grafite-600');

        new Chart(document.getElementById('grafico-resultado'), {
            type: 'bar',
            data: {
                labels: @json(array_column($opcoes, 'rotulo')),
                datasets: [{
                    data: @json(array_column($opcoes, 'votos')),
                    backgroundColor: @json(array_column($opcoes, 'hex')),
                    borderRadius: 4,
                    borderSkipped: 'start',
                    barThickness: 28,
                }],
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: token('--dt-cor-grafite-100') }, border: { display: false } },
                    y: { grid: { display: false }, border: { color: token('--dt-cor-grafite-300') } },
                },
            },
        });
    </script>
@endpush
