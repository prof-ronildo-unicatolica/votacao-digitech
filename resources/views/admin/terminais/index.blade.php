@extends('layouts.painel')

@section('titulo', 'Terminais')
@section('sobretitulo', 'Computadores de votação')
@section('menu', 'terminais')
@section('acoes')
    <x-botao icone="plus-lg" :href="route('admin.terminais.create')">Novo terminal</x-botao>
@endsection

@section('conteudo')
    <x-alerta tipo="info" titulo="Como ativar um terminal" class="mb-4">
        No computador da urna, entre como admin ou mesário e abra <code>{{ route('terminal.ativar') }}</code>.
        Escolha o terminal: o navegador fica pareado por {{ \App\Models\Terminal::VALIDADE_HORAS }} horas e o usuário é desconectado.
    </x-alerta>

    <x-cartao sem-padding>
        @if ($terminais->isEmpty())
            <x-estado-vazio icone="display" titulo="Nenhum terminal cadastrado">
                Cadastre um terminal para cada computador de votação.
            </x-estado-vazio>
        @else
            <div class="table-responsive">
                <table class="table dt-tabela align-middle">
                    <thead>
                        <tr><th>Terminal</th><th>Estado</th><th>Pareamento</th><th class="text-end" title="Eleitores liberados neste terminal">Sessões</th><th class="text-end">Ações</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($terminais as $terminal)
                            @php($estado = $terminal->estado())
                            <tr>
                                <td>
                                    <div class="fw-semibold">Terminal {{ $terminal->numero }}</div>
                                    @if ($terminal->local)<div class="small text-secondary">{{ $terminal->local }}</div>@endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($estado === 'livre')
                                        <x-selo tipo="sucesso" icone="check-circle-fill">Livre</x-selo>
                                    @elseif (in_array($estado, ['liberado', 'votando'], true))
                                        @if ($estado === 'votando')
                                            <x-selo tipo="info" icone="hourglass-split">Votando</x-selo>
                                        @else
                                            <x-selo tipo="marca" icone="unlock-fill">Liberado</x-selo>
                                        @endif
                                        <div class="small text-secondary">{{ $terminal->sessaoEmAndamento->eleitor->nome }}</div>
                                    @else
                                        <x-selo icone="dash-circle">Inativo</x-selo>
                                    @endif
                                </td>
                                <td class="small text-nowrap">
                                    @if ($terminal->estaAtivado())
                                        Ativado {{ $terminal->ativado_em->format('d/m H:i') }}<br>
                                        <span class="text-secondary">expira {{ $terminal->expiraEm()->format('d/m H:i') }}</span>
                                    @elseif ($terminal->ativado_em)
                                        <span class="text-secondary">Expirou {{ $terminal->expiraEm()->format('d/m H:i') }}</span>
                                    @else
                                        <span class="text-secondary">Não pareado</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $terminal->sessoes_count }}</td>
                                <td class="text-end text-nowrap">
                                    <x-botao variante="fantasma" tamanho="pequeno" icone="pencil" :href="route('admin.terminais.edit', $terminal)">Editar</x-botao>
                                    @if ($terminal->token_hash)
                                        <form method="POST" class="d-inline" action="{{ route('admin.terminais.desativar', $terminal) }}" onsubmit="return confirm('Desativar o terminal {{ $terminal->numero }}? O computador pareado para de funcionar como urna.')">
                                            @csrf
                                            <x-botao type="submit" variante="fantasma" tamanho="pequeno" icone="slash-circle">Desativar</x-botao>
                                        </form>
                                    @endif
                                    @if ($terminal->sessoes_count === 0)
                                        <form method="POST" class="d-inline" action="{{ route('admin.terminais.destroy', $terminal) }}" onsubmit="return confirm('Excluir o terminal {{ $terminal->numero }}? Isso não pode ser desfeito.')">
                                            @csrf @method('DELETE')
                                            <x-botao type="submit" variante="fantasma" tamanho="pequeno" icone="trash" class="text-danger">Excluir</x-botao>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-cartao>
    <p class="small text-secondary mt-3">
        <em>Liberado</em>: o mesário liberou o eleitor e a cédula ainda não apareceu na urna (H6). <em>Votando</em>: a cédula está na tela (H7).
        Terminal que já recebeu eleitores faz parte da ata e não pode ser excluído: desative.
    </p>
@endsection
