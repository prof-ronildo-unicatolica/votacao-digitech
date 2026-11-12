{{--
    Cargos de uma eleição (H13): a lista que toda chapa preenche, na ordem da cédula.
    Sem JavaScript: cada linha é um formulário (renomear), e as setas trocam de posição com o vizinho.
--}}
@extends('layouts.painel')

@php($total = $cargos->count())

@section('titulo', 'Cargos')
@section('sobretitulo', $eleicao->titulo)
@section('menu', 'cargos')
@section('acoes')
    <x-botao variante="contorno" icone="arrow-left" :href="route('admin.eleicoes.index')">Eleições</x-botao>
@endsection

@section('conteudo')
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-indicador rotulo="Cada chapa terá" :valor="$total.' '.($total === 1 ? 'candidato' : 'candidatos')" detalhe="um por cargo" />
        </div>
    </div>

    @if ($bloqueado)
        <x-alerta tipo="aviso" titulo="Cargos bloqueados" class="mb-4">
            Esta eleição já tem chapa inscrita. Mudar os cargos agora deixaria as chapas inscritas incompletas,
            então a lista ficou só para leitura (D14).
        </x-alerta>
    @elseif ($total === 0)
        <x-alerta tipo="aviso" titulo="Nenhum cargo definido" class="mb-4">
            As inscrições não podem abrir sem os cargos: o formulário de inscrição pede um candidato para cada um.
        </x-alerta>
    @endif

    <x-cartao sem-padding class="mb-4" style="max-width: 760px">
        @if ($total === 0)
            <x-estado-vazio icone="list-ol" titulo="Lista de cargos vazia">
                Adicione os cargos na ordem em que aparecem na cédula: Presidente, Vice-presidente, 1º Secretário...
            </x-estado-vazio>
        @else
            <ol class="list-unstyled m-0">
                @foreach ($cargos as $cargo)
                    @php($campo = 'titulo_'.$cargo->id)
                    <li class="d-flex flex-wrap align-items-start gap-2 px-4 py-3 @unless ($loop->last) border-bottom @endunless">
                        <span class="dt-mono fw-semibold pt-2" style="min-width: 2ch" aria-hidden="true">{{ $cargo->ordem }}.</span>

                        @if ($bloqueado)
                            <span class="pt-2 fw-semibold">{{ $cargo->titulo }}</span>
                        @else
                            <form method="POST" action="{{ route('admin.eleicoes.cargos.update', [$eleicao, $cargo]) }}" class="d-flex gap-2 flex-grow-1" novalidate>
                                @csrf @method('PUT')
                                <div class="flex-grow-1">
                                    <label for="{{ $campo }}" class="visually-hidden">Título do {{ $cargo->ordem }}º cargo</label>
                                    <input type="text" id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $cargo->titulo) }}" maxlength="80" required
                                        @class(['form-control', 'is-invalid' => $errors->has($campo)])>
                                    @error($campo)
                                        <div class="invalid-feedback"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                    @enderror
                                </div>
                                <x-botao type="submit" variante="secundario" tamanho="pequeno" icone="check-lg" class="align-self-start mt-1">Salvar</x-botao>
                            </form>

                            <div class="d-flex gap-1 mt-1">
                                @foreach (['cima' => ['arrow-up', 'Subir', $loop->first], 'baixo' => ['arrow-down', 'Descer', $loop->last]] as $direcao => [$icone, $rotulo, $naPonta])
                                    <form method="POST" action="{{ route('admin.eleicoes.cargos.mover', [$eleicao, $cargo]) }}">
                                        @csrf
                                        <input type="hidden" name="direcao" value="{{ $direcao }}">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $rotulo }}" aria-label="{{ $rotulo }} {{ $cargo->titulo }}" @disabled($naPonta)>
                                            <i class="bi bi-{{ $icone }}" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endforeach
                                <form method="POST" action="{{ route('admin.eleicoes.cargos.destroy', [$eleicao, $cargo]) }}" onsubmit="return confirm('Remover o cargo {{ e(addslashes($cargo->titulo)) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover" aria-label="Remover {{ $cargo->titulo }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </x-cartao>

    @unless ($bloqueado)
        <div class="row g-4" style="max-width: 760px">
            <div @class(['col-md-6' => $total === 0 && $origens->isNotEmpty(), 'col-12' => ! ($total === 0 && $origens->isNotEmpty())])>
                <form method="POST" action="{{ route('admin.eleicoes.cargos.store', $eleicao) }}" novalidate>
                    @csrf
                    <x-cartao titulo="Adicionar cargo">
                        <x-campo name="titulo" label="Título" :autofocus="$total === 0" maxlength="80" required
                            ajuda="Entra no fim da lista. Use as setas para mudar a posição." />
                        <x-botao type="submit" icone="plus-lg">Adicionar</x-botao>
                    </x-cartao>
                </form>
            </div>

            @if ($total === 0 && $origens->isNotEmpty())
                <div class="col-md-6">
                    <form method="POST" action="{{ route('admin.eleicoes.cargos.copiar', $eleicao) }}" novalidate>
                        @csrf
                        <x-cartao titulo="Copiar de outra eleição">
                            <div class="mb-4">
                                <label for="campo-origem" class="form-label">Eleição</label>
                                <select id="campo-origem" name="origem_id" @class(['form-select', 'form-select-lg', 'is-invalid' => $errors->has('origem_id')])>
                                    @foreach ($origens as $origem)
                                        <option value="{{ $origem->id }}" @selected(old('origem_id') === $origem->id)>{{ $origem->titulo }} ({{ $origem->cargos_count }} cargos)</option>
                                    @endforeach
                                </select>
                                @error('origem_id')
                                    <div class="invalid-feedback"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                                @enderror
                                <div class="form-text">Copia os títulos na mesma ordem, para não redigitar a cada semestre.</div>
                            </div>
                            <x-botao type="submit" variante="secundario" icone="copy">Copiar cargos</x-botao>
                        </x-cartao>
                    </form>
                </div>
            @endif
        </div>
    @endunless
@endsection
