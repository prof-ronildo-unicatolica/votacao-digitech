@extends('layouts.painel')

@section('titulo', 'Usuários')
@section('sobretitulo', 'Admins e mesários')
@section('menu', 'usuarios')
@section('acoes')
    <x-botao icone="person-plus" :href="route('admin.usuarios.create')">Novo usuário</x-botao>
@endsection

@section('conteudo')
    <x-cartao sem-padding>
        <div class="table-responsive">
            <table class="table dt-tabela align-middle">
                <thead>
                    <tr><th>Usuário</th><th>Perfil</th><th>Situação</th><th class="text-end" title="Eleitores liberados como mesário">Liberações</th><th class="text-end">Ações</th></tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        @php($euMesmo = $usuario->is(auth()->user()))
                        <tr @class(['text-secondary' => ! $usuario->ativo])>
                            <td>
                                <div class="fw-semibold">{{ $usuario->name }} @if ($euMesmo)<span class="small text-secondary fw-normal">(você)</span>@endif</div>
                                <div class="small text-secondary">{{ $usuario->email }}</div>
                            </td>
                            <td class="text-nowrap"><x-selo :tipo="$usuario->isAdmin() ? 'marca' : 'neutro'">{{ $usuario->perfil->rotulo() }}</x-selo></td>
                            <td>
                                @if ($usuario->ativo)
                                    <x-selo tipo="sucesso" icone="check-circle">Ativo</x-selo>
                                @else
                                    <x-selo tipo="perigo" icone="slash-circle">Desativado</x-selo>
                                @endif
                            </td>
                            <td class="text-end">{{ $usuario->sessoes_liberadas_count }}</td>
                            <td class="text-end text-nowrap">
                                <x-botao variante="fantasma" tamanho="pequeno" icone="pencil" :href="route('admin.usuarios.edit', $usuario)">Editar</x-botao>
                                @unless ($euMesmo)
                                    <form method="POST" class="d-inline" action="{{ route('admin.usuarios.ativo', $usuario) }}">
                                        @csrf
                                        <x-botao type="submit" variante="fantasma" tamanho="pequeno" :icone="$usuario->ativo ? 'slash-circle' : 'arrow-counterclockwise'">{{ $usuario->ativo ? 'Desativar' : 'Reativar' }}</x-botao>
                                    </form>
                                    @if ($usuario->sessoes_liberadas_count === 0)
                                        <form method="POST" class="d-inline" action="{{ route('admin.usuarios.destroy', $usuario) }}" onsubmit="return confirm('Excluir este usuário? Isso não pode ser desfeito.')">
                                            @csrf @method('DELETE')
                                            <x-botao type="submit" variante="fantasma" tamanho="pequeno" icone="trash" class="text-danger">Excluir</x-botao>
                                        </form>
                                    @endif
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-cartao>
    <p class="small text-secondary mt-3">Quem já liberou eleitores faz parte da ata e não pode ser excluído: desative.</p>
    <div class="mt-3">{{ $usuarios->links() }}</div>
@endsection
