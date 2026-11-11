@extends('layouts.painel')

@section('titulo', 'Importar eleitores')
@section('sobretitulo', 'Eleitores')
@section('menu', 'eleitores')

@section('conteudo')
    @if ($relatorio)
        <x-cartao titulo="Resultado da importação" class="mb-4">
            <div class="row g-3 mb-3">
                <div class="col-sm-4"><x-indicador rotulo="Cadastrados" :valor="$relatorio['criados']" /></div>
                <div class="col-sm-4"><x-indicador rotulo="Atualizados" :valor="$relatorio['atualizados']" /></div>
                <div class="col-sm-4"><x-indicador rotulo="Linhas com erro" :valor="$relatorio['total_falhas']" /></div>
            </div>

            @if ($relatorio['total_falhas'] === 0)
                <x-alerta tipo="sucesso">Todas as linhas foram importadas.</x-alerta>
            @else
                <x-alerta tipo="aviso" titulo="Algumas linhas não entraram" class="mb-3">
                    As outras linhas foram gravadas. Corrija estas no arquivo e importe de novo: matrícula repetida só atualiza o cadastro.
                </x-alerta>
                <div class="table-responsive">
                    <table class="table dt-tabela align-middle">
                        <thead><tr><th>Linha</th><th>Matrícula</th><th>Motivo</th></tr></thead>
                        <tbody>
                            @foreach ($relatorio['falhas'] as $falha)
                                <tr>
                                    <td class="text-nowrap">{{ $falha['linha'] }}</td>
                                    <td class="dt-mono text-nowrap">{{ $falha['matricula'] !== '' ? $falha['matricula'] : '—' }}</td>
                                    <td>{{ $falha['motivo'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($relatorio['total_falhas'] > count($relatorio['falhas']))
                    <p class="small text-secondary mt-2">Mostrando as primeiras {{ count($relatorio['falhas']) }} de {{ $relatorio['total_falhas'] }} linhas com erro.</p>
                @endif
            @endif

            <x-slot:acoes>
                <x-botao variante="contorno" tamanho="pequeno" :href="route('admin.eleitores.index')">Ver eleitores</x-botao>
            </x-slot:acoes>
        </x-cartao>
    @endif

    <form method="POST" action="{{ route('admin.eleitores.importar.store') }}" enctype="multipart/form-data" novalidate style="max-width: 560px">
        @csrf
        <x-cartao class="mb-4">
            <p>
                Uma linha por aluno, com cabeçalho <code>matricula;nome;turma;email</code>. Turma e e-mail são opcionais.
                Separador <code>;</code> (padrão do Excel em português) ou <code>,</code>.
            </p>
            <p class="mb-4">
                <a href="{{ route('admin.eleitores.modelo') }}"><i class="bi bi-download" aria-hidden="true"></i> Baixar o modelo</a>
            </p>
            <x-campo name="arquivo" type="file" label="Arquivo CSV" accept=".csv,text/csv" required
                ajuda="Matrícula que já existe atualiza o nome (e a turma e o e-mail, se vierem preenchidos). Linhas com erro aparecem no relatório; as outras são gravadas." />
        </x-cartao>

        <div class="d-flex gap-2">
            <x-botao type="submit" icone="upload">Importar</x-botao>
            <x-botao variante="contorno" :href="route('admin.eleitores.index')">Cancelar</x-botao>
        </div>
    </form>
@endsection
