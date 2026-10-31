{{--
    Opção de voto na urna (H8): um rádio grande. Todas as chapas usam o MESMO estilo, na ordem do número
    (neutralidade). Branco/nulo usa :neutra="true".
    Uso: <x-opcao-chapa numero="10" nome="Inovação Acadêmica" value="{{ $chapa->id }}" />
--}}
@props(['numero' => null, 'nome', 'descricao' => null, 'name' => 'chapa_id', 'value' => '', 'neutra' => false, 'checked' => false])

<label {{ $attributes->class(['dt-opcao', 'dt-opcao--neutra' => $neutra]) }}>
    <input type="radio" name="{{ $name }}" value="{{ $value }}" required @checked($checked)>
    <span class="dt-opcao-numero" aria-hidden="{{ $numero ? 'false' : 'true' }}">{{ $numero ?? '—' }}</span>
    <span>
        <span class="dt-opcao-nome">{{ $nome }}</span>
        @if ($descricao)<span class="dt-opcao-descricao d-block">{{ $descricao }}</span>@endif
    </span>
    <span class="dt-opcao-marcador" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
</label>
