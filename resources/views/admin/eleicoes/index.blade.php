@extends('layouts.painel')

@section('titulo', 'Eleições')
@section('sobretitulo', 'Comissão eleitoral')
@section('menu', 'eleicoes')
@section('acoes')
    <x-botao icone="plus-lg" :href="route('admin.eleicoes.create')">Nova eleição</x-botao>
@endsection

@section('conteudo')
    <x-cartao sem-padding>
        @if ($eleicoes->isEmpty())
            <x-estado-vazio icone="calendar-plus" titulo="Nenhuma eleição cadastrada">
                Comece cadastrando a eleição com os períodos de inscrição e de votação.
            </x-estado-vazio>
        @else
            <div class="table-responsive">
                <table class="table dt-tabela align-middle">
                    <thead>
                        <tr><th>Eleição</th><th>Fase</th><th>Inscrições</th><th>Votação</th><th class="text-end">Ações</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($eleicoes as $eleicao)
                            @php($fase = $eleicao->fase())
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $eleicao->titulo ?: 'Sem título' }}</div>
                                    @if ($eleicao->ativa)
                                        <x-selo tipo="marca" icone="star-fill">Ativa</x-selo>
                                    @endif
                                    <span class="small text-secondary">{{ $eleicao->chapas_count }} {{ $eleicao->chapas_count === 1 ? 'chapa' : 'chapas' }}</span>
                                </td>
                                <td>
                                    @if ($fase)
                                        <x-selo :tipo="$fase->selo()" :icone="$fase->icone()">{{ $fase->rotulo() }}</x-selo>
                                    @else
                                        <x-selo tipo="aviso">Datas incompletas</x-selo>
                                    @endif
                                </td>
                                <td class="small text-nowrap">de {{ $eleicao->inscricao_abre_em?->format('d/m/Y H:i') }}<br>até {{ $eleicao->inscricao_fecha_em?->format('d/m/Y H:i') }}</td>
                                <td class="small text-nowrap">de {{ $eleicao->votacao_abre_em?->format('d/m/Y H:i') }}<br>até {{ $eleicao->votacao_fecha_em?->format('d/m/Y H:i') }}</td>
                                <td class="text-end text-nowrap">
                                    <x-botao variante="fantasma" tamanho="pequeno" icone="pencil" :href="route('admin.eleicoes.edit', $eleicao)">Editar</x-botao>
                                    <form method="POST" class="d-inline" action="{{ route($eleicao->ativa ? 'admin.eleicoes.desativar' : 'admin.eleicoes.ativar', $eleicao) }}">
                                        @csrf
                                        <x-botao type="submit" variante="fantasma" tamanho="pequeno" :icone="$eleicao->ativa ? 'pause-circle' : 'play-circle'">{{ $eleicao->ativa ? 'Desativar' : 'Ativar' }}</x-botao>
                                    </form>
                                    <form method="POST" class="d-inline" action="{{ route('admin.eleicoes.destroy', $eleicao) }}"
                                        onsubmit="return confirm('Excluir a eleição {{ e(addslashes($eleicao->titulo)) }}? Isso não pode ser desfeito.')">
                                        @csrf @method('DELETE')
                                        <x-botao type="submit" variante="fantasma" tamanho="pequeno" icone="trash" class="text-danger">Excluir</x-botao>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-cartao>
    <div class="mt-3">{{ $eleicoes->links() }}</div>
@endsection
