{{--
    Decisão no fim da conferência de uma importação (D19): cancelar (rollback), ignorar os erros ou confirmar.
    Uso: <x-decisao-importacao :action="route(...confirmar)" :voltar="route(...importar)" :assinatura="$analise->assinatura()"
            :prontas="3" :linhas-com-erro="[4, 7]" item="chapa" itens="chapas">texto quando tudo está pronto</x-decisao-importacao>
    `item`/`itens` são palavras femininas (chapa, linha).
--}}
@props(['action', 'voltar', 'assinatura', 'prontas', 'linhasComErro' => [], 'item', 'itens', 'bloqueio' => null])

@php
    $erradas = count($linhasComErro);
    $contar = fn (int $n) => $n.' '.($n === 1 ? $item : $itens);
@endphp

<form method="POST" action="{{ $action }}" novalidate>
    @csrf
    <input type="hidden" name="assinatura" value="{{ $assinatura }}">

    <x-cartao titulo="O que fazer?" class="mb-4">
        @error('acao')
            <x-alerta tipo="perigo" class="mb-3">{{ $message }}</x-alerta>
        @enderror

        @if (! $erradas)
            <p class="mb-0">{{ $slot }} Tudo é gravado junto: se algo não puder ser gravado, nada é (rollback).</p>
            <input type="hidden" name="acao" value="importar">
        @elseif (! $prontas)
            <p class="mb-0">Nenhuma {{ $item }} está pronta. Corrija o arquivo e envie de novo, ou cancele.</p>
        @else
            <fieldset>
                <legend class="fs-6 fw-semibold mb-3">{{ $contar($erradas) }} com erro. Escolha:</legend>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="acao" id="acao-cancelar" value="cancelar" @checked(old('acao') === 'cancelar')>
                    <label class="form-check-label" for="acao-cancelar">
                        <strong>Cancelar tudo (rollback).</strong> Nada é gravado e o arquivo é descartado. Corrija e envie de novo.
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="acao" id="acao-ignorar" value="ignorar" @checked(old('acao') === 'ignorar')>
                    <label class="form-check-label" for="acao-ignorar">
                        <strong>Ignorar os erros</strong> e importar só {{ $prontas === 1 ? "a {$item} pronta" : "as {$prontas} {$itens} prontas" }}.
                        {{ $erradas === 1 ? "A {$item} com erro fica de fora (linha " : "As {$erradas} com erro ficam de fora (linhas " }}{{ \App\Support\Nomes::lista(array_slice($linhasComErro, 0, 30)) }}{{ $erradas > 30 ? ' e outras' : '' }}).
                    </label>
                </div>
            </fieldset>
        @endif
    </x-cartao>

    <div class="d-flex flex-wrap gap-2">
        @if ($prontas && ! $bloqueio)
            <x-botao type="submit" icone="check-lg">{{ $erradas ? 'Confirmar' : 'Confirmar importação de '.$contar($prontas) }}</x-botao>
        @endif
        <x-botao type="submit" name="acao" value="cancelar" variante="contorno" icone="x-lg" formnovalidate>Cancelar importação</x-botao>
        <x-botao variante="fantasma" :href="$voltar" icone="upload">Enviar outro arquivo</x-botao>
    </div>
</form>
