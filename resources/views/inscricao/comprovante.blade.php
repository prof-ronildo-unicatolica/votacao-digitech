{{-- Comprovante da inscrição (H14). Só aparece para quem acabou de inscrever, na mesma sessão do navegador. --}}
@extends('layouts.app')

@section('titulo', 'Comprovante de inscrição')

@section('conteudo')
    <div style="max-width: 640px" class="mx-auto">
        <div class="dt-cabecalho-pagina">
            <div>
                <div class="dt-sobretitulo">{{ $chapa->eleicao->titulo }}</div>
                <h1>Inscrição recebida</h1>
            </div>
        </div>

        <x-alerta tipo="sucesso" class="mb-4">
            A chapa foi inscrita e está <strong>pendente</strong>: a comissão eleitoral ainda vai homologar a inscrição.
            Guarde este comprovante (imprima ou tire um print).
        </x-alerta>

        <x-cartao class="mb-4">
            <dl class="row g-3 mb-4">
                <div class="col-sm-4">
                    <dt class="small text-secondary fw-normal">Número da chapa</dt>
                    <dd class="display-6 fw-semibold mb-0">{{ $chapa->numero }}</dd>
                </div>
                <div class="col-sm-8">
                    <dt class="small text-secondary fw-normal">Inscrita em</dt>
                    <dd class="h4 mb-0">{{ $chapa->created_at->format('d/m/Y \à\s H:i') }}</dd>
                </div>
            </dl>
            <h2 class="h4 mb-1">{{ $chapa->nome }}</h2>
            <p class="mb-4"><x-selo :tipo="$chapa->situacao->selo()">{{ $chapa->situacao->rotulo() }}</x-selo></p>

            <table class="table dt-tabela align-middle mb-0">
                <thead><tr><th>Cargo</th><th>Candidato</th></tr></thead>
                <tbody>
                    @foreach ($composicao as $candidato)
                        <tr>
                            <td>{{ $candidato->cargo->titulo }}</td>
                            <td>
                                <div class="fw-semibold">{{ $candidato->eleitor->nome }}</div>
                                <div class="small text-secondary dt-mono">{{ $candidato->eleitor->matricula }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-cartao>

        <div class="d-flex gap-2 mb-5 d-print-none">
            <x-botao icone="printer" onclick="window.print()">Imprimir</x-botao>
        </div>
    </div>
@endsection
