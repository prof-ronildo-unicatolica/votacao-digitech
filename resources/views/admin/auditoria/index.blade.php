{{--
    H11 fase 3 · Auditoria (só admin). Filtros na URL (GET), para o link poder ser guardado e
    o CSV exportar exatamente o que está na tela. Detalhes em <details>, sem JavaScript.
--}}
@extends('layouts.painel')

@php
    use App\Enums\AcaoAuditoria;
    $alvoFiltrado = $filtro->get('alvo_tipo') !== '' && $filtro->get('alvo_id') !== '';
@endphp

@section('titulo', 'Auditoria')
@section('sobretitulo', 'Quem fez o quê, quando e de onde')
@section('menu', 'auditoria')
@section('acoes')
    <x-botao variante="contorno" icone="filetype-csv" :href="route('admin.auditoria.csv', $filtro->valores)">Exportar CSV</x-botao>
@endsection

@section('conteudo')
    <form method="GET" action="{{ route('admin.auditoria') }}" class="dt-cartao p-3 mb-4">
        @if ($alvoFiltrado)
            <input type="hidden" name="alvo_tipo" value="{{ $filtro->get('alvo_tipo') }}">
            <input type="hidden" name="alvo_id" value="{{ $filtro->get('alvo_id') }}">
        @endif
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-xl-3">
                <label for="f-eleicao" class="form-label">Eleição</label>
                <select id="f-eleicao" name="eleicao" class="form-select">
                    <option value="todas" @selected($filtro->get('eleicao') === 'todas')>Todas (inclui acessos e usuários)</option>
                    @foreach ($eleicoes as $e)
                        <option value="{{ $e->id }}" @selected($filtro->get('eleicao') === $e->id)>{{ $e->titulo }}{{ $e->ativa ? ' (ativa)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label for="f-grupo" class="form-label">Categoria</label>
                <select id="f-grupo" name="grupo" class="form-select">
                    <option value="">Todas</option>
                    @foreach (AcaoAuditoria::GRUPOS as $chave => $rotulo)
                        <option value="{{ $chave }}" @selected($filtro->get('grupo') === $chave)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label for="f-acao" class="form-label">Ação</label>
                <select id="f-acao" name="acao" class="form-select">
                    <option value="">Todas</option>
                    @foreach (AcaoAuditoria::GRUPOS as $chave => $rotulo)
                        <optgroup label="{{ $rotulo }}">
                            @foreach (AcaoAuditoria::doGrupo($chave) as $acao)
                                <option value="{{ $acao->value }}" @selected($filtro->get('acao') === $acao->value)>{{ $acao->rotulo() }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label for="f-usuario" class="form-label">Quem fez</label>
                <select id="f-usuario" name="usuario" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $u)
                        <option value="{{ $u->id }}" @selected($filtro->get('usuario') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label for="f-de" class="form-label">De</label>
                <input type="date" id="f-de" name="de" value="{{ $filtro->get('de') }}" class="form-control">
            </div>
            <div class="col-6 col-xl-2">
                <label for="f-ate" class="form-label">Até</label>
                <input type="date" id="f-ate" name="ate" value="{{ $filtro->get('ate') }}" class="form-control">
            </div>
            <div class="col-md-8 col-xl-5">
                <label for="f-busca" class="form-label">Buscar</label>
                <input type="search" id="f-busca" name="busca" value="{{ $filtro->get('busca') }}" class="form-control" placeholder="Matrícula, número da chapa, e-mail, nome de quem fez">
            </div>
            <div class="col-md-4 col-xl-3 d-flex gap-2">
                <x-botao type="submit" icone="funnel">Filtrar</x-botao>
                <x-botao variante="fantasma" :href="route('admin.auditoria')">Limpar</x-botao>
            </div>
        </div>
    </form>

    @if ($alvoFiltrado)
        <x-alerta tipo="info" class="mb-4">
            Mostrando o histórico de um só registro ({{ $filtro->get('alvo_tipo') }}).
            <a href="{{ route('admin.auditoria', \Illuminate\Support\Arr::except($filtro->valores, ['alvo_tipo', 'alvo_id'])) }}">Ver todos os eventos</a>.
        </x-alerta>
    @endif

    <x-cartao :titulo="$eventos->total().' '.($eventos->total() === 1 ? 'evento' : 'eventos')" subtitulo="Mais recentes primeiro. O registro só acrescenta: nada aqui pode ser alterado ou apagado pela aplicação." sem-padding>
        @if ($eventos->isEmpty())
            <x-estado-vazio icone="shield-check" titulo="Nenhum evento com estes filtros">
                Tente outra eleição ou "Todas": acessos e cadastros de usuários não pertencem a uma eleição.
            </x-estado-vazio>
        @else
            <div class="table-responsive">
                <table class="table dt-tabela align-top">
                    <thead><tr><th>Quando</th><th>Ação</th><th>Quem</th><th>IP</th><th>Detalhes</th></tr></thead>
                    <tbody>
                        @foreach ($eventos as $evento)
                            <tr>
                                <td class="text-nowrap">{{ $evento->ocorrido_em->format('d/m/Y') }}<br><span class="dt-mono">{{ $evento->ocorrido_em->format('H:i:s') }}</span></td>
                                <td>
                                    {{ $evento->acao->rotulo() }}
                                    <div class="small text-body-secondary dt-mono">{{ $evento->acao->value }}</div>
                                </td>
                                <td>{{ $evento->ator }}</td>
                                <td class="dt-mono small">{{ $evento->ip ?? '—' }}</td>
                                <td style="min-width: 16rem">
                                    <div class="small">{{ \Illuminate\Support\Str::limit($evento->resumo(), 160) ?: '—' }}</div>
                                    <details class="small mt-1">
                                        <summary>Ver detalhes</summary>
                                        <dl class="mb-1 mt-2">
                                            <dt>Evento</dt><dd class="dt-mono">{{ $evento->id }}</dd>
                                            @if ($evento->alvo_tipo)
                                                <dt>Alvo</dt>
                                                <dd>
                                                    {{ $evento->alvo_tipo }} <span class="dt-mono">{{ $evento->alvo_id }}</span>
                                                    · <a href="{{ route('admin.auditoria', ['eleicao' => 'todas', 'alvo_tipo' => $evento->alvo_tipo, 'alvo_id' => $evento->alvo_id]) }}">histórico</a>
                                                </dd>
                                            @endif
                                        </dl>
                                        <pre class="dt-mono small bg-body-tertiary p-2 rounded mb-0" style="white-space: pre-wrap">{{ json_encode($evento->dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-cartao>

    <div class="mt-4">{{ $eventos->links() }}</div>
@endsection
