@extends('layouts.painel')

@section('titulo', 'Importar eleitores')
@section('sobretitulo', 'Eleitores')
@section('menu', 'eleitores')

@section('conteudo')
    @if (session('aviso'))
        <x-alerta tipo="aviso" class="mb-4">{{ session('aviso') }}</x-alerta>
    @endif

    @if ($resultado)
        <x-cartao titulo="Resultado da importação" class="mb-4">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3"><x-indicador rotulo="Cadastrados" :valor="$resultado['criados']" /></div>
                <div class="col-6 col-md-3"><x-indicador rotulo="Atualizados" :valor="$resultado['atualizados']" /></div>
                <div class="col-6 col-md-3"><x-indicador rotulo="Sem alteração" :valor="$resultado['iguais']" /></div>
                <div class="col-6 col-md-3"><x-indicador rotulo="Linhas ignoradas" :valor="count($resultado['ignoradas'])" /></div>
            </div>

            @if ($resultado['ignoradas'])
                <h3 class="h6">Ignoradas (não foram gravadas)</h3>
                <ul class="small mb-0">
                    @foreach (array_slice($resultado['ignoradas'], 0, 200) as $linha)
                        @foreach ($linha['erros'] as $erro)
                            <li>Linha {{ $linha['linha'] }}@if ($erro['coluna']), {{ \App\Support\ImportadorEleitores::ROTULOS[$erro['coluna']] ?? $erro['coluna'] }}@endif: {{ $erro['motivo'] }}</li>
                        @endforeach
                    @endforeach
                </ul>
                @if (count($resultado['ignoradas']) > 200)
                    <p class="small text-secondary mt-2 mb-0">Mostrando as primeiras 200 de {{ count($resultado['ignoradas']) }} linhas ignoradas.</p>
                @endif
            @else
                <x-alerta tipo="sucesso">Todas as linhas do arquivo foram importadas.</x-alerta>
            @endif

            <x-slot:acoes>
                <x-botao variante="contorno" tamanho="pequeno" :href="route('admin.eleitores.index')">Ver eleitores</x-botao>
            </x-slot:acoes>
        </x-cartao>
    @endif

    @if ($emConferencia)
        <x-alerta tipo="info" class="mb-4">
            O arquivo <strong>{{ $emConferencia['nome'] }}</strong> está esperando conferência.
            <a href="{{ route('admin.eleitores.importar.conferir') }}">Voltar para a conferência</a>. Enviar outro arquivo descarta este.
        </x-alerta>
    @endif

    <form method="POST" action="{{ route('admin.eleitores.importar.store') }}" enctype="multipart/form-data" novalidate style="max-width: 640px">
        @csrf
        <x-cartao class="mb-4">
            <p>
                Uma linha por aluno, com cabeçalho <code>matricula;nome;turma;email</code>. Turma e e-mail são opcionais.
                Separador <code>;</code> (padrão do Excel em português) ou <code>,</code>.
            </p>
            <p>Matrícula que já existe <strong>atualiza</strong> o cadastro: o nome sempre; turma e e-mail só se vierem preenchidos.</p>
            <p class="mb-4">
                <a href="{{ route('admin.eleitores.modelo') }}"><i class="bi bi-download" aria-hidden="true"></i> Baixar o modelo</a>
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
                    Nada é gravado ao enviar: primeiro você confere o que é novo, o que muda e o que tem erro, e só depois confirma.
                </div>
            </div>
        </x-cartao>

        <div class="d-flex gap-2">
            <x-botao type="submit" icone="upload">Enviar para conferência</x-botao>
            <x-botao variante="contorno" :href="route('admin.eleitores.index')">Voltar</x-botao>
        </div>
    </form>
@endsection
