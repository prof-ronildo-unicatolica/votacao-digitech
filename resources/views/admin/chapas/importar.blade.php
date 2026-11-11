@extends('layouts.painel')

@section('titulo', 'Importar chapas')
@section('sobretitulo', 'Chapas')
@section('menu', 'chapas')

@section('conteudo')
    @if (session('aviso'))
        <x-alerta tipo="aviso" class="mb-4">{{ session('aviso') }}</x-alerta>
    @endif

    @if ($resultado)
        <x-cartao titulo="Resultado da importação" class="mb-4">
            <div class="row g-3 mb-3">
                <div class="col-sm-4"><x-indicador rotulo="Chapas importadas" :valor="count($resultado['importadas'])" /></div>
                <div class="col-sm-4"><x-indicador rotulo="Chapas ignoradas" :valor="count($resultado['ignoradas'])" /></div>
                <div class="col-sm-4"><x-indicador rotulo="Cargos criados" :valor="count($resultado['cargos_criados'])" /></div>
            </div>

            <x-alerta tipo="sucesso" class="mb-3">
                As chapas importadas entraram como <strong>pendentes</strong> e passam pela homologação (H3).
                @if ($resultado['cargos_criados'])
                    Cargos criados pelo cabeçalho: {{ \App\Support\Nomes::lista($resultado['cargos_criados']) }}.
                @endif
            </x-alerta>

            <div class="table-responsive mb-3">
                <table class="table dt-tabela align-middle">
                    <thead><tr><th>Número</th><th>Chapa</th></tr></thead>
                    <tbody>
                        @foreach ($resultado['importadas'] as $chapa)
                            <tr><td class="dt-mono">{{ $chapa['numero'] }}</td><td>{{ $chapa['nome'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($resultado['ignoradas'])
                <h3 class="h6">Ignoradas (não foram gravadas)</h3>
                <ul class="small mb-0">
                    @foreach ($resultado['ignoradas'] as $chapa)
                        @foreach ($chapa['erros'] as $erro)
                            <li>Linha {{ $chapa['linha'] }}@if ($erro['coluna']), {{ $erro['coluna'] }}@endif: {{ $erro['motivo'] }}</li>
                        @endforeach
                    @endforeach
                </ul>
            @endif
        </x-cartao>
    @endif

    @if ($bloqueio)
        <x-alerta tipo="aviso" titulo="Importação indisponível">
            {{ $bloqueio }} Chapas só podem ser importadas durante o período de inscrição da eleição ativa.
        </x-alerta>
    @else
        @if ($emConferencia)
            <x-alerta tipo="info" class="mb-4">
                O arquivo <strong>{{ $emConferencia['nome'] }}</strong> está esperando conferência.
                <a href="{{ route('admin.chapas.importar.conferir') }}">Voltar para a conferência</a>. Enviar outro arquivo descarta este.
            </x-alerta>
        @endif

        <form method="POST" action="{{ route('admin.chapas.importar.store') }}" enctype="multipart/form-data" novalidate style="max-width: 640px">
            @csrf
            <x-cartao class="mb-4">
                <p>
                    Uma linha por chapa, para a eleição <strong>{{ $eleicao->titulo }}</strong>. O cabeçalho tem
                    <code>numero_chapa;nome_chapa</code> e depois <strong>uma coluna por cargo</strong>, com a matrícula do candidato.
                    <code>contato_email</code> e <code>contato_telefone</code> são opcionais.
                </p>
                @if ($eleicao->cargos()->exists())
                    <p>As colunas de cargo precisam ser os cargos desta eleição: {{ \App\Support\Nomes::lista($eleicao->cargos()->pluck('titulo')->all()) }}.</p>
                @else
                    <p>Esta eleição ainda não tem cargos: <strong>as colunas do cabeçalho vão criá-los</strong>, na ordem das colunas.</p>
                @endif
                <p class="mb-4">
                    <a href="{{ route('admin.chapas.modelo') }}"><i class="bi bi-download" aria-hidden="true"></i> Baixar o modelo</a>
                </p>

                <div class="mb-2">
                    <label for="campo-arquivo" class="form-label">Arquivo CSV</label>
                    <input type="file" id="campo-arquivo" name="arquivo" accept=".csv,text/csv" required aria-describedby="campo-arquivo-ajuda"
                        @class(['form-control', 'form-control-lg', 'is-invalid' => $errors->has('arquivo')])>
                    @if ($errors->has('arquivo'))
                        <div class="invalid-feedback">
                            <i class="bi bi-exclamation-circle" aria-hidden="true"></i> O arquivo não pôde ser lido:
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->get('arquivo') as $erro)
                                    <li>{{ $erro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div id="campo-arquivo-ajuda" class="form-text">
                        Nada é gravado ao enviar: primeiro você confere cada linha e só depois confirma.
                    </div>
                </div>
            </x-cartao>

            <div class="d-flex gap-2">
                <x-botao type="submit" icone="upload">Enviar para conferência</x-botao>
                <x-botao variante="contorno" :href="route('admin.painel')">Voltar</x-botao>
            </div>
        </form>
    @endif
@endsection
