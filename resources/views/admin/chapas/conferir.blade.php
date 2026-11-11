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

    <form method="POST" action="{{ route('admin.chapas.importar.confirmar') }}" novalidate>
        @csrf
        <input type="hidden" name="assinatura" value="{{ $analise->assinatura() }}">

        <x-cartao titulo="O que fazer?" class="mb-4">
            @error('acao')
                <x-alerta tipo="perigo" class="mb-3">{{ $message }}</x-alerta>
            @enderror

            @if (! $comErro)
                <p class="mb-0">
                    As {{ count($validas) }} chapas estão prontas. Ao confirmar, elas entram como <strong>pendentes</strong>,
                    todas juntas: se alguma não puder ser gravada, nenhuma é (rollback).
                </p>
                <input type="hidden" name="acao" value="importar">
            @elseif (! $validas)
                <p class="mb-0">Nenhuma chapa está pronta. Corrija o arquivo e envie de novo, ou cancele.</p>
            @else
                <fieldset>
                    <legend class="fs-6 fw-semibold mb-3">{{ count($comErro) }} {{ count($comErro) === 1 ? 'chapa tem' : 'chapas têm' }} erro. Escolha:</legend>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="acao" id="acao-cancelar" value="cancelar" @checked(old('acao') === 'cancelar')>
                        <label class="form-check-label" for="acao-cancelar">
                            <strong>Cancelar tudo (rollback).</strong> Nenhuma chapa é gravada e o arquivo é descartado. Corrija e envie de novo.
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="acao" id="acao-ignorar" value="ignorar" @checked(old('acao') === 'ignorar')>
                        <label class="form-check-label" for="acao-ignorar">
                            <strong>Ignorar os erros</strong> e importar só {{ count($validas) === 1 ? 'a chapa pronta' : 'as '.count($validas).' chapas prontas' }}.
                            {{ count($comErro) === 1 ? 'A chapa com erro fica de fora (linha ' : 'As '.count($comErro).' com erro ficam de fora (linhas ' }}{{ Nomes::lista(array_column($comErro, 'linha')) }}).
                        </label>
                    </div>
                </fieldset>
            @endif
        </x-cartao>

        <div class="d-flex flex-wrap gap-2">
            @if ($validas && ! $bloqueio)
                <x-botao type="submit" icone="check-lg">{{ $comErro ? 'Confirmar' : 'Confirmar importação de '.count($validas).' '.(count($validas) === 1 ? 'chapa' : 'chapas') }}</x-botao>
            @endif
            <x-botao type="submit" name="acao" value="cancelar" variante="contorno" icone="x-lg" formnovalidate>Cancelar importação</x-botao>
            <x-botao variante="fantasma" :href="route('admin.chapas.importar')" icone="upload">Enviar outro arquivo</x-botao>
        </div>
    </form>
@endsection
