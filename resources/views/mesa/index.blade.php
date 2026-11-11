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

    <div id="painel-indicadores">@include('mesa._indicadores')</div>

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
                                                Terminal {{ $terminal->numero }}{{ $terminal->local ? ' ('.$terminal->local.')' : '' }} · {{ ['livre' => 'Livre', 'liberado' => 'Liberado', 'votando' => 'Votando', 'inativo' => 'Inativo'][$estado] }}
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
            <div id="painel-terminais">@include('mesa._terminais')</div>
            <p class="small text-secondary mt-3" id="painel-atualizado">Atualiza sozinho a cada 5 segundos.</p>
        </div>
    </div>
    @push('scripts')
        <script>
            // H9 · O painel se atualiza sozinho, sem recarregar a página (o que a pessoa digitou
            // na matrícula continua lá). Pausa com a aba escondida ou com um "Cancelar sessão" aberto.
            (() => {
                const url = @json(route('mesario.painel'));
                const indicadores = document.getElementById('painel-indicadores');
                const terminais = document.getElementById('painel-terminais');
                const atualizado = document.getElementById('painel-atualizado');
                const seletor = document.getElementById('campo-terminal');
                const rotulos = { livre: 'Livre', liberado: 'Liberado', votando: 'Votando', inativo: 'Inativo' };

                const atualizar = async () => {
                    if (document.hidden || terminais.querySelector('details[open]')) return;
                    try {
                        const resposta = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                        if (resposta.status === 401 || resposta.status === 419 || resposta.redirected) return location.reload();
                        if (! resposta.ok) return;
                        const painel = await resposta.json();
                        indicadores.innerHTML = painel.indicadores;
                        terminais.innerHTML = painel.terminais_html;
                        atualizado.textContent = 'Atualizado às ' + painel.atualizado_em + ' · atualiza sozinho a cada 5 segundos.';
                        // O seletor de terminal do formulário acompanha os estados.
                        if (seletor) {
                            for (const terminal of painel.terminais) {
                                const opcao = seletor.querySelector(`option[value="${terminal.id}"]`);
                                if (! opcao) continue;
                                opcao.disabled = terminal.estado !== 'livre';
                                opcao.textContent = opcao.textContent.replace(/· [^·]+$/, '· ' + rotulos[terminal.estado]);
                            }
                        }
                    } catch (erro) {
                        // Rede instável: tenta de novo na próxima volta.
                    }
                };
                setInterval(atualizar, 5000);
                document.addEventListener('visibilitychange', () => { if (! document.hidden) atualizar(); });
            })();
        </script>
    @endpush
@endsection
