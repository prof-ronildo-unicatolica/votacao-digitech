@extends('layouts.painel')

@php
    use App\Support\AnaliseImportacao;
    use App\Support\Nomes;

    $validas = $analise->validas();
    $comErro = $analise->comErro();
    $colunas = 3 + count($analise->cargos) + 1;
@endphp

@section('titulo', 'Conferir importação')
@section('sobretitulo', 'Chapas')
@section('menu', 'chapas')

@section('conteudo')
    @if (session('aviso'))
        <x-alerta tipo="aviso" class="mb-4">{{ session('aviso') }}</x-alerta>
    @endif

    <p class="text-secondary">
        Arquivo <strong>{{ $arquivo }}</strong> para a eleição <strong>{{ $eleicao->titulo }}</strong>.
        <strong>Nada foi gravado ainda.</strong>
    </p>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><x-indicador rotulo="Chapas no arquivo" :valor="count($analise->chapas)" /></div>
        <div class="col-6 col-md-3"><x-indicador rotulo="Prontas" :valor="count($validas)" /></div>
        <div class="col-6 col-md-3"><x-indicador rotulo="Com erro" :valor="count($comErro)" /></div>
        <div class="col-6 col-md-3"><x-indicador rotulo="Cargos" :valor="count($analise->cargos)" :detalhe="$analise->criaCargos ? 'serão criados' : 'conferem com a eleição'" /></div>
    </div>

    @if ($analise->criaCargos)
        <x-alerta tipo="info" titulo="O cabeçalho vai criar os cargos" class="mb-4">
            A eleição ainda não tem cargos. Ao confirmar, serão criados, nesta ordem:
            {{ Nomes::lista(array_column($analise->cargos, 'titulo')) }}.
            Depois da primeira chapa, os cargos ficam bloqueados (D14).
        </x-alerta>
    @endif

    @if ($analise->ajustes)
        <x-cartao titulo="Nomes padronizados" subtitulo="Espaços, maiúsculas e minúsculas e ordinais (1º, 2º) foram ajustados. Confira se ficou como deveria." class="mb-4">
            <ul class="small mb-0">
                @foreach ($analise->ajustes as $ajuste)
                    <li>{{ $ajuste }}</li>
                @endforeach
            </ul>
        </x-cartao>
    @endif

    <x-cartao titulo="Linhas do arquivo" :subtitulo="$comErro ? 'Os campos com erro estão em vermelho, com o motivo logo abaixo da linha.' : 'Todas as linhas estão prontas.'" sem-padding class="mb-4">
        <div class="table-responsive">
            <table class="table dt-tabela align-middle dt-tabela-importacao">
                <thead>
                    <tr>
                        <th>Linha</th>
                        <th>Nº</th>
                        <th>Chapa</th>
                        @foreach ($analise->cargos as $cargo)
                            <th>{{ $cargo['titulo'] }}</th>
                        @endforeach
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analise->chapas as $chapa)
                        @php($erradas = AnaliseImportacao::colunasComErro($chapa))
                        <tr @class(['dt-linha-erro' => $chapa['erros']])>
                            <td class="dt-mono">{{ $chapa['linha'] }}</td>
                            <td @class(['dt-mono', 'dt-celula-erro' => in_array('numero_chapa', $erradas)])>{{ $chapa['numero_bruto'] !== '' ? $chapa['numero_bruto'] : '—' }}</td>
                            <td @class(['dt-celula-erro' => in_array('nome_chapa', $erradas)])>{{ $chapa['nome'] !== '' ? $chapa['nome'] : '—' }}</td>
                            @foreach ($analise->cargos as $chave => $cargo)
                                @php($candidato = $chapa['candidatos'][$cargo['chave']])
                                <td @class(['dt-celula-erro' => in_array($cargo['titulo'], $erradas)])>
                                    <span class="dt-mono">{{ $candidato['matricula'] !== '' ? $candidato['matricula'] : '—' }}</span>
                                    @if ($candidato['aluno'])<div class="small text-secondary">{{ $candidato['aluno'] }}</div>@endif
                                </td>
                            @endforeach
                            <td class="text-nowrap">
                                @if ($chapa['erros'])
                                    <x-selo tipo="perigo" icone="x-circle">{{ count($chapa['erros']) }} {{ count($chapa['erros']) === 1 ? 'erro' : 'erros' }}</x-selo>
                                @else
                                    <x-selo tipo="sucesso" icone="check-circle">Pronta</x-selo>
                                @endif
                            </td>
                        </tr>
                        @if ($chapa['erros'])
                            <tr class="dt-linha-erro-detalhe">
                                <td colspan="{{ $colunas }}">
                                    <ul class="small mb-0">
                                        @foreach ($chapa['erros'] as $erro)
                                            <li>
                                                <strong>Linha {{ $chapa['linha'] }}@if ($erro['coluna']), {{ $erro['coluna'] }}@endif:</strong>
                                                {{ $erro['motivo'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($analise->linhasEmBranco)
            <p class="small text-secondary px-4 py-2 mb-0">{{ $analise->linhasEmBranco }} {{ $analise->linhasEmBranco === 1 ? 'linha em branco foi ignorada' : 'linhas em branco foram ignoradas' }}.</p>
        @endif
    </x-cartao>

    @if ($bloqueio)
        <x-alerta tipo="aviso" titulo="Importação indisponível" class="mb-4">{{ $bloqueio }}</x-alerta>
    @endif

    <x-decisao-importacao :action="route('admin.chapas.importar.confirmar')" :voltar="route('admin.chapas.importar')"
        :assinatura="$analise->assinatura()" :prontas="count($validas)" :linhas-com-erro="array_column($comErro, 'linha')"
        item="chapa" itens="chapas" :bloqueio="$bloqueio">
        {{ count($validas) === 1 ? 'A chapa está pronta' : 'As '.count($validas).' chapas estão prontas' }} e {{ count($validas) === 1 ? 'entra' : 'entram' }} como <strong>pendente{{ count($validas) === 1 ? '' : 's' }}</strong>.
    </x-decisao-importacao>
@endsection
