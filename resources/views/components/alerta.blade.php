{{-- Mensagem com cor + ícone + texto. Tipos: info, sucesso, aviso, perigo. Uso: <x-alerta tipo="perigo" titulo="Eleitor já votou">...</x-alerta> --}}
@props(['tipo' => 'info', 'titulo' => null])

@php
    $icone = ['info' => 'info-circle-fill', 'sucesso' => 'check-circle-fill', 'aviso' => 'exclamation-triangle-fill', 'perigo' => 'x-octagon-fill'][$tipo] ?? 'info-circle-fill';
@endphp

<div {{ $attributes->class(['dt-alerta', "dt-alerta--$tipo"]) }} role="{{ in_array($tipo, ['perigo', 'aviso']) ? 'alert' : 'status' }}">
    <i class="bi bi-{{ $icone }}" aria-hidden="true"></i>
    <div class="dt-alerta-conteudo">
        @if ($titulo)<div class="dt-alerta-titulo">{{ $titulo }}</div>@endif
        {{ $slot }}
    </div>
</div>
