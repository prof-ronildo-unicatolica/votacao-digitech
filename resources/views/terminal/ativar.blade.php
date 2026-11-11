{{--
    Pareamento do computador da urna (H5, D16). Abra ESTA página no computador de votação.
    POST /terminal/ativar → App\Http\Controllers\TerminalAtivacaoController.
--}}
@extends('layouts.app')

@section('titulo', 'Ativar terminal')

@section('conteudo')
    <div class="dt-cabecalho-pagina"><h1>Ativar este computador como terminal</h1></div>

    <form method="POST" action="{{ route('terminal.ativar.store') }}" novalidate style="max-width: 640px">
        @csrf
        <x-alerta tipo="aviso" titulo="Faça isto no computador da urna" class="mb-4">
            Ao ativar, este navegador passa a ser o terminal escolhido por {{ \App\Models\Terminal::VALIDADE_HORAS }} horas
            e <strong>você será desconectado</strong>, para o eleitor nunca encontrar uma sessão de mesário aberta.
        </x-alerta>

        <x-cartao class="mb-4">
            @if ($terminais->isEmpty())
                <x-estado-vazio icone="display" titulo="Nenhum terminal cadastrado">
                    O admin cadastra os terminais em <strong>Admin → Terminais</strong>.
                </x-estado-vazio>
            @else
                <fieldset>
                    <legend class="form-label">Qual terminal é este computador?</legend>
                    @foreach ($terminais as $terminal)
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="terminal_id" id="terminal-{{ $terminal->numero }}" value="{{ $terminal->id }}" @checked(old('terminal_id') === $terminal->id) required>
                            <label class="form-check-label" for="terminal-{{ $terminal->numero }}">
                                <strong>Terminal {{ $terminal->numero }}</strong>@if ($terminal->local) · {{ $terminal->local }}@endif
                                @if ($terminal->estaAtivado())
                                    <span class="d-block small text-secondary">
                                        Já ativado em outro computador desde {{ $terminal->ativado_em->format('d/m H:i') }}. Ativar aqui desativa o outro.
                                    </span>
                                @endif
                            </label>
                        </div>
                    @endforeach
                    @error('terminal_id')
                        <div class="text-danger small"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                    @enderror
                </fieldset>
            @endif
        </x-cartao>

        @if ($terminais->isNotEmpty())
            <x-botao type="submit" icone="display">Ativar e abrir a urna</x-botao>
        @endif
    </form>
@endsection
