{{--
    Campo de formulário com rótulo, ajuda e erro de validação do Laravel.
    Uso: <x-campo name="matricula" label="Matrícula" mono autofocus ajuda="Só números" />
--}}
@props(['name', 'label', 'type' => 'text', 'ajuda' => null, 'mono' => false, 'tamanho' => 'grande', 'value' => null])

@php($id = $attributes->get('id', 'campo-'.$name))
<div class="mb-4">
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ old($name, $value) }}"
        @if ($ajuda) aria-describedby="{{ $id }}-ajuda" @endif
        {{ $attributes->except('id')->class([
            'form-control',
            'form-control-lg' => $tamanho === 'grande',
            'dt-mono' => $mono,
            'is-invalid' => $errors->has($name),
        ]) }}>
    @error($name)
        <div class="invalid-feedback"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
    @enderror
    @if ($ajuda)<div id="{{ $id }}-ajuda" class="form-text">{{ $ajuda }}</div>@endif
</div>
