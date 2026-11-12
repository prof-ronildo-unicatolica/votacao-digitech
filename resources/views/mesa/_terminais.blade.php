{{-- Terminais da mesa (H6, H9): livre, liberado, votando ou inativo, com "Cancelar sessão". --}}
@if ($terminais->isEmpty())
    <x-cartao>
        <x-estado-vazio icone="display" titulo="Nenhum terminal cadastrado">A comissão cadastra os terminais em Admin → Terminais.</x-estado-vazio>
    </x-cartao>
@else
    <div class="row g-3">
        @foreach ($terminais as $terminal)
            @php($sessao = $terminal->sessaoEmAndamento)
            @php($estado = $terminal->estado())
            <div class="col-md-6">
                <x-status-terminal :numero="$terminal->numero" :estado="$estado"
                    :detalhe="match ($estado) {
                        'liberado' => $sessao->eleitor->nome.' · expira em '.$sessao->minutosRestantes().' min',
                        'votando' => $sessao->eleitor->nome.' na cabine · expira em '.$sessao->minutosRestantes().' min',
                        'livre' => $terminal->local ?: 'Pronto para liberar',
                        default => 'Computador não ativado',
                    }">
                    @if (in_array($estado, ['liberado', 'votando'], true))
                        <details class="dt-acao mt-3">
                            <summary class="btn btn-perigo btn-sm"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar sessão</summary>
                            <form method="POST" action="{{ route('mesario.cancelar', $sessao) }}" class="dt-acao-painel">
                                @csrf
                                <p class="small mb-2">{{ $sessao->eleitor->nome }} ainda não votou. Cancelar libera o terminal, e a liberação pode ser feita de novo.</p>
                                <x-botao type="submit" variante="perigo" tamanho="pequeno">Confirmar cancelamento</x-botao>
                            </form>
                        </details>
                    @endif
                </x-status-terminal>
            </div>
        @endforeach
    </div>
@endif
