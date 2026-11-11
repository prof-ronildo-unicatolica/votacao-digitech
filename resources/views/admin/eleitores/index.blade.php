@extends('layouts.painel')

@section('titulo', 'Eleitores')
@section('sobretitulo', 'Eleitorado')
@section('menu', 'eleitores')
@section('acoes')
    <x-botao variante="contorno" icone="upload" :href="route('admin.eleitores.importar')">Importar CSV</x-botao>
    <x-botao icone="person-plus" :href="route('admin.eleitores.create')">Novo eleitor</x-botao>
@endsection

@section('conteudo')
    @if ($total === 0)
        <x-cartao>
            <x-estado-vazio icone="person-vcard" titulo="Nenhum eleitor cadastrado">
                Importe a lista de alunos por CSV antes de abrir as inscrições: os candidatos das chapas são indicados pela matrícula.
                <div class="mt-4 d-flex gap-2 justify-content-center">
                    <x-botao icone="upload" :href="route('admin.eleitores.importar')">Importar CSV</x-botao>
                    <x-botao variante="contorno" :href="route('admin.eleitores.create')">Cadastrar um a um</x-botao>
                </div>
            </x-estado-vazio>
        </x-cartao>
    @else
        <form method="GET" action="{{ route('admin.eleitores.index') }}" class="d-flex gap-2 mb-3" role="search" style="max-width: 560px">
            <label for="busca" class="visually-hidden">Buscar por matrícula ou nome</label>
            <input type="search" id="busca" name="busca" value="{{ $busca }}" class="form-control" placeholder="Matrícula ou nome" autocomplete="off">
            <x-botao type="submit" variante="secundario" icone="search">Buscar</x-botao>
            @if ($busca !== '')
                <x-botao variante="fantasma" :href="route('admin.eleitores.index')">Limpar</x-botao>
            @endif
        </form>

        <x-cartao sem-padding>
            @if ($eleitores->isEmpty())
                <x-estado-vazio icone="search" titulo="Nenhum eleitor encontrado">
                    Nada com “{{ $busca }}” na matrícula ou no nome.
                </x-estado-vazio>
            @else
                <div class="table-responsive">
                    <table class="table dt-tabela align-middle">
                        <thead>
                            <tr><th>Matrícula</th><th>Eleitor</th><th>Turma</th><th>Situação</th><th class="text-end">Ações</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($eleitores as $eleitor)
                                @php($bloqueado = $eleitor->sessoes_count > 0 || $eleitor->candidaturas_count > 0)
                                <tr>
                                    <td class="dt-mono text-nowrap">{{ $eleitor->matricula }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $eleitor->nome ?: 'Sem nome' }}</div>
                                        @if ($eleitor->email)<div class="small text-secondary">{{ $eleitor->email }}</div>@endif
                                    </td>
                                    <td class="text-nowrap">{{ $eleitor->turma ?? '—' }}</td>
                                    <td class="text-nowrap">
                                        @if ($eleitor->candidaturas_count > 0)
                                            <x-selo tipo="marca" icone="people">Candidato</x-selo>
                                        @endif
                                        @if ($eleitor->sessoes_count > 0)
                                            <x-selo tipo="neutro" icone="journal-check">Na ata</x-selo>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <x-botao variante="fantasma" tamanho="pequeno" icone="pencil" :href="route('admin.eleitores.edit', $eleitor)">Editar</x-botao>
                                        @unless ($bloqueado)
                                            <form method="POST" class="d-inline" action="{{ route('admin.eleitores.destroy', $eleitor) }}" onsubmit="return confirm('Excluir este eleitor? Isso não pode ser desfeito.')">
                                                @csrf @method('DELETE')
                                                <x-botao type="submit" variante="fantasma" tamanho="pequeno" icone="trash" class="text-danger">Excluir</x-botao>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-cartao>
        <p class="small text-secondary mt-3">
            {{ $total }} {{ $total === 1 ? 'eleitor cadastrado' : 'eleitores cadastrados' }}.
            Quem é candidato ou já foi liberado para votar (está na ata) não pode ser excluído.
        </p>
        <div class="mt-3">{{ $eleitores->links() }}</div>
    @endif
@endsection
