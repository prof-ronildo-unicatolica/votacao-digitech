{{-- Lista sem itens. Uso: <x-estado-vazio icone="people" titulo="Nenhum eleitor">Importe um CSV para começar.</x-estado-vazio> --}}
@props(['icone' => 'inbox', 'titulo'])

<div {{ $attributes->class('dt-estado-vazio') }}>
    <i class="bi bi-{{ $icone }}" aria-hidden="true"></i>
    <h3>{{ $titulo }}</h3>
    <div>{{ $slot }}</div>
</div>
