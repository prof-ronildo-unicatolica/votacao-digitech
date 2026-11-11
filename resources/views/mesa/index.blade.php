@extends('layouts.app')

@php
    use App\Enums\SituacaoSessao;

    $livres = $terminais->filter(fn ($t) => $t->estado() === 'livre');
    $pode = $consulta && ! isset($consulta['erro']) && $consulta['pode'] && ! $bloqueio;
@endphp

@section('titulo', 'Mesa receptora')

@section('conteudo')
    <div class="dt-cabecalho-pagina">
        <div>
            @if ($eleicao)
                @php($fase = $eleicao->fase())
                <div class="dt-sobretitulo">{{ $eleicao->titulo }} @if ($fase)· <x-selo :tipo="$fase->selo()" :icone="$fase->icone()">{{ $fase->rotulo() }}</x-selo>@endif</div>
            @endif
            <h1>Mesa receptora</h1>
        </div>
        <x-botao variante="contorno" icone="display" :href="route('terminal.ativar')">Ativar este computador como terminal</x-botao>
    </div>

    @if (session('status'))
        <x-alerta tipo="sucesso" class="mb-4">{{ session('status') }}</x-alerta>
    @endif
    @error('geral')
        <x-alerta tipo="perigo" titulo="Não foi possível" class="mb-4">{{ $message }}</x-alerta>
    @enderror

    @if ($eleicao)
        <div class="row g-3 mb-5">
            <div class="col-sm-4">
                <x-indicador rotulo="Comparecimento" :valor="$votaram"
                    :detalhe="'de '.$aptos.' aptos · '.($aptos ? round(100 * $votaram / $aptos) : 0).'%'"
                    :progresso="$aptos ? 100 * $votaram / $aptos : 0" />
            </div>
            <div class="col-sm-4"><x-indicador rotulo="Terminais livres" :valor="$livres->count()" :detalhe="'de '.$terminais->count()" /></div>
            <div class="col-sm-4"><x-indicador rotulo="Votação encerra" :valor="$eleicao->votacao_fecha_em?->format('H:i') ?? '—'" :detalhe="$eleicao->votacao_fecha_em?->format('d/m/Y')" /></div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <x-cartao titulo="Liberar eleitor" subtitulo="Confira o documento antes de liberar.">
                @if ($bloqueio)
                    <x-alerta tipo="aviso" titulo="Liberação indisponível">{{ $bloqueio }}</x-alerta>
                @else
                    <form method="GET" action="{{ route('mesario') }}" class="mb-3" novalidate>
                        <label for="campo-matricula" class="form-label">Matrícula</label>
                        <div class="d-flex gap-2">
                            <input id="campo-matricula" name="matricula" value="{{ $matricula }}" inputmode="numeric" autocomplete="off" required
                                @if (! $consulta) autofocus @endif class="form-control form-control-lg dt-mono">
                            <x-botao type="submit" variante="contorno" icone="search">Conferir</x-botao>
                        </div>
                    </form>

                    @if ($consulta && isset($consulta['erro']))
                        <x-alerta tipo="perigo">{{ $consulta['erro'] }}</x-alerta>
                    @elseif ($consulta)
                        @php($eleitor = $consulta['eleitor'])
                        <x-alerta :tipo="$consulta['pode'] ? 'info' : 'perigo'" :titulo="$eleitor->nome" class="mb-4">
                            <span class="dt-mono">{{ $eleitor->matricula }}</span>@if ($eleitor->turma) · {{ $eleitor->turma }}@endif
                            @if ($consulta['motivo'])
                                <div class="mt-2">{{ $consulta['motivo'] }}</div>
                            @elseif ($consulta['situacao'] && $consulta['situacao']->permiteReliberar())
                                <div class="mt-2">Sessão anterior {{ \Illuminate\Support\Str::lower($consulta['situacao']->rotulo()) }}: liberar de novo reaproveita a mesma sessão.</div>
                            @endif
                        </x-alerta>

                        @if ($pode)
                            <form method="POST" action="{{ route('mesario.liberar') }}" novalidate>
                                @csrf
                                <input type="hidden" name="matricula" value="{{ $eleitor->matricula }}">
                                <div class="mb-4">
                                    <label class="form-label" for="campo-terminal">Terminal</label>
                                    <select id="campo-terminal" name="terminal_id" class="form-select form-select-lg" required>
                                        @foreach ($terminais as $terminal)
                                            @php($estado = $terminal->estado())
                                            <option value="{{ $terminal->id }}" @disabled($estado !== 'livre') @selected($terminal->is($livres->first()))>
                                                Terminal {{ $terminal->numero }}{{ $terminal->local ? ' ('.$terminal->local.')' : '' }} · {{ ['livre' => 'Livre', 'liberado' => 'Liberado', 'inativo' => 'Inativo'][$estado] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($livres->isEmpty())
                                        <div class="form-text text-danger">Nenhum terminal livre agora. Espere um eleitor terminar ou cancele uma sessão.</div>
                                    @endif
                                </div>
                                <x-botao type="submit" tamanho="grande" icone="unlock" class="w-100" :disabled="$livres->isEmpty()">Liberar {{ $eleitor->nome }}</x-botao>
                            </form>
                        @endif
                    @endif
                @endif
            </x-cartao>
        </div>

        <div class="col-lg-7">
            <h2 class="h5 mb-3">Terminais</h2>
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
                                    'livre' => $terminal->local ?: 'Pronto para liberar',
                                    default => 'Computador não ativado',
                                }">
                                @if ($estado === 'liberado')
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
                <p class="small text-secondary mt-3">Atualize a página para ver o estado mais recente. A atualização automática é a H9.</p>
            @endif
        </div>
    </div>
@endsection
