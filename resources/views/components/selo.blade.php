{{-- Selo de situação. Tipos: neutro, sucesso, aviso, perigo, info, marca. Uso: <x-selo tipo="sucesso" icone="check">Ativa</x-selo> --}}
@props(['tipo' => 'neutro', 'icone' => null])

<span {{ $attributes->class(['dt-selo', "dt-selo--$tipo" => $tipo !== 'neutro']) }}>
    @if ($icone)<i class="bi bi-{{ $icone }}" aria-hidden="true"></i>@endif{{ $slot }}
</span>
