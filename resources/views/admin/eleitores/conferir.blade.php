@extends('layouts.painel')

@php
    use App\Support\ImportadorEleitores;

    $comErro = $analise->comErro();
    $validas = $analise->validas();
    $novos = $analise->com('novo');
    $atualizar = $analise->com('atualizar');
    $ajustes = $analise->ajustes();
    $limite = 200;
    $rotulo = fn (?string $coluna) => ImportadorEleitores::ROTULOS[$coluna] ?? $coluna;
@endphp

@section('titulo', 'Conferir importação')
@section('sobretitulo', 'Eleitores')
@section('menu', 'eleitores')

@section('conteudo')
    @if (session('aviso'))
        <x-alerta tipo="aviso" class="mb-4">{{ session('aviso') }}</x-alerta>
    @endif

    <p class="text-secondary">Arquivo <strong>{{ $arquivo }}</strong>. <strong>Nada foi gravado ainda.</strong></p>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><x-indicador rotulo="Novos" :valor="count($novos)" /></div>
        <div class="col-6 col-md-3"><x-indicador rotulo="Atualizados" :valor="count($atualizar)" /></div>
        <div class="col-6 col-md-3"><x-indicador rotulo="Sem alteração" :valor="count($analise->com('igual'))" detalhe="já cadastrados assim" /></div>
        <div class="col-6 col-md-3"><x-indicador rotulo="Com erro" :valor="count($comErro)" /></div>
    </div>

    @if ($analise->colunasIgnoradas)
        <x-alerta tipo="info" class="mb-4">
            Colunas ignoradas (não fazem parte do cadastro): {{ \App\Support\Nomes::lista(array_map(fn ($c) => "“{$c}”", $analise->colunasIgnoradas)) }}.
        </x-alerta>
    @endif

    @if ($comErro)
        <x-cartao titulo="Linhas com erro" subtitulo="Os campos com erro estão em vermelho, com o motivo logo abaixo." sem-padding class="mb-4">
            <div class="table-responsive">
                <table class="table dt-tabela align-middle dt-tabela-importacao">
                    <thead><tr><th>Linha</th><th>Matrícula</th><th>Nome</th><th>Turma</th><th>E-mail</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($comErro, 0, $limite) as $linha)
                            @php($erradas = array_column($linha['erros'], 'coluna'))
                            <tr class="dt-linha-erro">
                                <td class="dt-mono">{{ $linha['linha'] }}</td>
                                @foreach (['matricula', 'nome', 'turma', 'email'] as $coluna)
                                    <td @class(['dt-mono' => $coluna === 'matricula', 'dt-celula-erro' => in_array($coluna, $erradas, true)])>{{ $linha[$coluna] ?? '—' ?: '—' }}</td>
                                @endforeach
                            </tr>
                            <tr class="dt-linha-erro-detalhe">
                                <td colspan="5">
                                    <ul class="small mb-0">
                                        @foreach ($linha['erros'] as $erro)
                                            <li><strong>Linha {{ $linha['linha'] }}@if ($erro['coluna']), {{ $rotulo($erro['coluna']) }}@endif:</strong> {{ $erro['motivo'] }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($comErro) > $limite)
                <p class="small text-secondary px-4 py-2 mb-0">Mostrando as primeiras {{ $limite }} de {{ count($comErro) }} linhas com erro.</p>
            @endif
        </x-cartao>
    @endif

    @if ($atualizar)
        <x-cartao titulo="Cadastros que vão mudar" subtitulo="Matrícula que já existe atualiza o cadastro. Turma e e-mail vazios no arquivo não apagam o que já está gravado." sem-padding class="mb-4">
            <div class="table-responsive">
                <table class="table dt-tabela align-middle">
                    <thead><tr><th>Linha</th><th>Matrícula</th><th>O que muda</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($atualizar, 0, $limite) as $linha)
                            <tr>
                                <td class="dt-mono">{{ $linha['linha'] }}</td>
                                <td class="dt-mono">{{ $linha['matricula'] }}</td>
                                <td>
                                    @foreach ($linha['mudancas'] as $campo => [$antes, $depois])
                                        <div><span class="text-secondary">{{ $rotulo($campo) }}:</span> <s>{{ $antes ?? '—' }}</s> → <strong>{{ $depois }}</strong></div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($atualizar) > $limite)
                <p class="small text-secondary px-4 py-2 mb-0">Mostrando as primeiras {{ $limite }} de {{ count($atualizar) }} atualizações.</p>
            @endif
        </x-cartao>
    @endif

    @if ($ajustes)
        <x-cartao titulo="Nomes padronizados" subtitulo="Nomes em CAIXA ALTA ou todo em minúsculas foram arrumados. Confira se ficou como deveria." class="mb-4">
            <ul class="small mb-0">
                @foreach (array_slice($ajustes, 0, 50) as $linha)
                    <li>Linha {{ $linha['linha'] }}: “{{ \Illuminate\Support\Str::squish($linha['nome_original']) }}” → “{{ $linha['nome'] }}”</li>
                @endforeach
            </ul>
            @if (count($ajustes) > 50)
                <p class="small text-secondary mt-2 mb-0">E mais {{ count($ajustes) - 50 }} nomes.</p>
            @endif
        </x-cartao>
    @endif

    @if ($novos)
        <details class="dt-cartao mb-4 p-3">
            <summary class="fw-semibold">{{ count($novos) }} {{ count($novos) === 1 ? 'aluno novo' : 'alunos novos' }} (ver lista)</summary>
            <div class="table-responsive mt-3">
                <table class="table dt-tabela align-middle">
                    <thead><tr><th>Linha</th><th>Matrícula</th><th>Nome</th><th>Turma</th><th>E-mail</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($novos, 0, $limite) as $linha)
                            <tr>
                                <td class="dt-mono">{{ $linha['linha'] }}</td>
                                <td class="dt-mono">{{ $linha['matricula'] }}</td>
                                <td>{{ $linha['nome'] }}</td>
                                <td>{{ $linha['turma'] ?? '—' }}</td>
                                <td>{{ $linha['email'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($novos) > $limite)
                <p class="small text-secondary mb-0">Mostrando os primeiros {{ $limite }} de {{ count($novos) }}.</p>
            @endif
        </details>
    @endif

    @if ($analise->linhasEmBranco)
        <p class="small text-secondary">{{ $analise->linhasEmBranco }} {{ $analise->linhasEmBranco === 1 ? 'linha em branco foi ignorada' : 'linhas em branco foram ignoradas' }}.</p>
    @endif

    <x-decisao-importacao :action="route('admin.eleitores.importar.confirmar')" :voltar="route('admin.eleitores.importar')"
        :assinatura="$analise->assinatura()" :prontas="count($validas)" :linhas-com-erro="array_column($comErro, 'linha')"
        item="linha" itens="linhas">
        {{ count($validas) === 1 ? 'A linha está pronta' : 'As '.count($validas).' linhas estão prontas' }}:
        {{ count($novos) }} {{ count($novos) === 1 ? 'cadastro novo' : 'cadastros novos' }} e {{ count($atualizar) }} {{ count($atualizar) === 1 ? 'atualização' : 'atualizações' }}.
    </x-decisao-importacao>
@endsection
