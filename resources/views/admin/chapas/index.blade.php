@extends('layouts.painel')

@php
    use App\Enums\SituacaoChapa;
    use App\Support\HomologacaoDeChapa;

    $total = $contagem->sum();
    $homologadas = (int) ($contagem[SituacaoChapa::Homologada->value] ?? 0);
    $pendentes = (int) ($contagem[SituacaoChapa::Pendente->value] ?? 0);
    // Formulário que falhou: abre o painel certo de novo, com o erro.
    $falhou = fn ($chapa, string $alvo) => old('chapa_id') === $chapa->id && old('alvo', '') === $alvo;
@endphp

@section('titulo', 'Chapas')
@section('sobretitulo', $eleicao?->titulo ?? 'Comissão eleitoral')
@section('menu', 'chapas')
@section('acoes')
    <x-botao variante="contorno" icone="upload" :href="route('admin.chapas.importar')">Importar CSV</x-botao>
@endsection

@section('conteudo')
    @if (! $eleicao)
        <x-cartao>
            <x-estado-vazio icone="calendar-plus" titulo="Nenhuma eleição cadastrada">
                Cadastre a eleição e os cargos antes de receber as inscrições.
            </x-estado-vazio>
        </x-cartao>
    @else
        @if ($eleicoes->count() > 1)
            <form method="GET" class="d-flex flex-wrap align-items-end gap-2 mb-4" style="max-width: 560px">
                <div class="flex-grow-1">
                    <label for="campo-eleicao" class="form-label">Eleição</label>
                    <select id="campo-eleicao" name="eleicao" class="form-select">
                        @foreach ($eleicoes as $opcao)
                            <option value="{{ $opcao->id }}" @selected($opcao->id === $eleicao->id)>{{ $opcao->titulo }}{{ $opcao->ativa ? ' (ativa)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <x-botao type="submit" variante="contorno">Ver</x-botao>
            </form>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><x-indicador rotulo="Inscritas" :valor="$total" /></div>
            <div class="col-6 col-md-3"><x-indicador rotulo="Pendentes" :valor="$pendentes" /></div>
            <div class="col-6 col-md-3"><x-indicador rotulo="Homologadas" :valor="$homologadas" detalhe="vão para a urna" /></div>
            <div class="col-6 col-md-3"><x-indicador rotulo="Indeferidas" :valor="(int) ($contagem[SituacaoChapa::Indeferida->value] ?? 0)" /></div>
        </div>

        @if ($bloqueio)
            <x-alerta tipo="info" titulo="Somente leitura" class="mb-4">{{ $bloqueio }}</x-alerta>
        @else
            @if ($total > 0 && $homologadas === 0)
                <x-alerta tipo="aviso" titulo="Nenhuma chapa homologada" class="mb-4">
                    A votação abre em {{ $eleicao->votacao_abre_em->format('d/m/Y \à\s H:i') }} e não pode começar sem ao menos uma chapa homologada.
                </x-alerta>
            @endif
            <p class="text-secondary small mb-4">
                @if ($composicaoAberta)
                    A composição pode ser corrigida até o fim das inscrições ({{ $eleicao->inscricao_fecha_em->format('d/m/Y \à\s H:i') }}).
                @else
                    Inscrições encerradas: a composição congelou (D14). A situação pode mudar até a votação abrir ({{ $eleicao->votacao_abre_em->format('d/m/Y \à\s H:i') }}).
                @endif
            </p>
        @endif

        <nav class="nav nav-pills gap-2 mb-4" aria-label="Filtrar por situação">
            <a href="{{ route('admin.chapas.index', ['eleicao' => $eleicao->id]) }}" @class(['nav-link', 'active' => ! $filtro]) @if (! $filtro) aria-current="page" @endif>Todas ({{ $total }})</a>
            @foreach (SituacaoChapa::cases() as $situacao)
                <a href="{{ route('admin.chapas.index', ['eleicao' => $eleicao->id, 'situacao' => $situacao->value]) }}"
                    @class(['nav-link', 'active' => $filtro === $situacao]) @if ($filtro === $situacao) aria-current="page" @endif>
                    {{ $situacao->rotulo() }}s ({{ (int) ($contagem[$situacao->value] ?? 0) }})
                </a>
            @endforeach
        </nav>

        @forelse ($chapas as $chapa)
            <x-cartao id="chapa-{{ $chapa->numero }}" :titulo="'Chapa '.$chapa->numero.' · '.$chapa->nome"
                :subtitulo="'Inscrita por '.$chapa->origem->rotulo().' em '.$chapa->created_at->format('d/m/Y H:i').($chapa->contato_email ? ' · '.$chapa->contato_email : '').($chapa->contato_telefone ? ' · '.$chapa->contato_telefone : '')"
                class="mb-4 dt-chapa">
                <x-slot:acoes>
                    <x-selo :tipo="$chapa->situacao->selo()">{{ $chapa->situacao->rotulo() }}</x-selo>
                </x-slot:acoes>

                @if ($chapa->situacao === SituacaoChapa::Indeferida)
                    <x-alerta tipo="perigo" titulo="Motivo do indeferimento" class="mb-3">{{ $chapa->motivo_indeferimento }}</x-alerta>
                @endif
                @if ($chapa->analisada_em)
                    <p class="small text-secondary">
                        {{ ($chapa->situacao === SituacaoChapa::Pendente ? 'Análise reaberta' : $chapa->situacao->rotulo()).' em '.$chapa->analisada_em->format('d/m/Y H:i') }}@if ($chapa->analisadaPor) por {{ $chapa->analisadaPor->name }}@endif.
                    </p>
                @endif

                <div class="table-responsive mb-3">
                    <table class="table dt-tabela align-middle">
                        <thead><tr><th>Cargo</th><th>Candidato</th><th>Matrícula</th>@if ($composicaoAberta && ! $bloqueio)<th class="text-end">Corrigir</th>@endif</tr></thead>
                        <tbody>
                            @foreach ($chapa->composicao as $candidato)
                                <tr>
                                    <td>{{ $candidato->cargo->titulo }}</td>
                                    <td>{{ $candidato->eleitor->nome }}</td>
                                    <td class="dt-mono">{{ $candidato->eleitor->matricula }}</td>
                                    @if ($composicaoAberta && ! $bloqueio)
                                        @php($aberto = $falhou($chapa, 'cargo-'.$candidato->cargo_id))
                                        <td class="text-end">
                                            <details class="dt-acao" @if ($aberto) open @endif>
                                                <summary class="btn btn-fantasma btn-sm"><i class="bi bi-pencil" aria-hidden="true"></i> Trocar</summary>
                                                <form method="POST" action="{{ route('admin.chapas.candidatos.update', [$chapa, $candidato->cargo]) }}" class="dt-acao-painel text-start" novalidate>
                                                    @csrf @method('PUT')
                                                    <label class="form-label small" for="matricula-{{ $candidato->id }}">Nova matrícula para {{ $candidato->cargo->titulo }}</label>
                                                    <div class="d-flex gap-2">
                                                        <input id="matricula-{{ $candidato->id }}" name="matricula" value="{{ $aberto ? old('matricula') : '' }}" required
                                                            @class(['form-control', 'dt-mono', 'is-invalid' => $aberto && $errors->has('matricula')])>
                                                        <x-botao type="submit" tamanho="pequeno">Salvar</x-botao>
                                                    </div>
                                                    @if ($aberto)
                                                        @error('matricula')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                    @endif
                                                    @if ($chapa->situacao === SituacaoChapa::Homologada)
                                                        <div class="form-text">A chapa está homologada: trocar um candidato a devolve para pendente.</div>
                                                    @endif
                                                </form>
                                            </details>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @unless ($bloqueio)
                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        @if ($chapa->situacao === SituacaoChapa::Pendente)
                            <details class="dt-acao">
                                <summary class="btn btn-primary btn-sm"><i class="bi bi-check-lg" aria-hidden="true"></i> Homologar</summary>
                                <form method="POST" action="{{ route('admin.chapas.homologar', $chapa) }}" class="dt-acao-painel">
                                    @csrf
                                    <p class="small mb-2">Conferiu a composição? A chapa {{ $chapa->numero }} vai aparecer na urna.</p>
                                    <x-botao type="submit" tamanho="pequeno" icone="check-lg">Confirmar homologação</x-botao>
                                </form>
                            </details>

                            @php($aberto = $falhou($chapa, ''))
                            <details class="dt-acao" @if ($aberto && $errors->has('motivo')) open @endif>
                                <summary class="btn btn-contorno btn-sm"><i class="bi bi-x-lg" aria-hidden="true"></i> Indeferir</summary>
                                <form method="POST" action="{{ route('admin.chapas.indeferir', $chapa) }}" class="dt-acao-painel" novalidate>
                                    @csrf
                                    <label for="motivo-{{ $chapa->id }}" class="form-label small">Motivo (fica registrado; informe o representante pelo contato da chapa)</label>
                                    <textarea id="motivo-{{ $chapa->id }}" name="motivo" rows="3" maxlength="{{ HomologacaoDeChapa::MOTIVO_MAX }}" required
                                        @class(['form-control', 'mb-2', 'is-invalid' => $aberto && $errors->has('motivo')])>{{ $aberto ? old('motivo') : '' }}</textarea>
                                    @if ($aberto)
                                        @error('motivo')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                                    @endif
                                    <x-botao type="submit" variante="perigo" tamanho="pequeno">Confirmar indeferimento</x-botao>
                                </form>
                            </details>
                        @else
                            <details class="dt-acao">
                                <summary class="btn btn-contorno btn-sm"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reabrir análise</summary>
                                <form method="POST" action="{{ route('admin.chapas.reabrir', $chapa) }}" class="dt-acao-painel">
                                    @csrf
                                    <p class="small mb-2">A chapa volta para <strong>pendente</strong>{{ $chapa->situacao === SituacaoChapa::Homologada ? ' e sai da urna' : '' }} até uma nova decisão.</p>
                                    <x-botao type="submit" variante="contorno" tamanho="pequeno">Confirmar</x-botao>
                                </form>
                            </details>
                        @endif

                        <details class="dt-acao ms-auto">
                            <summary class="btn btn-fantasma btn-sm text-danger"><i class="bi bi-trash" aria-hidden="true"></i> Excluir</summary>
                            <form method="POST" action="{{ route('admin.chapas.destroy', $chapa) }}" class="dt-acao-painel">
                                @csrf @method('DELETE')
                                <p class="small mb-2">Apaga a chapa {{ $chapa->numero }} e a composição. Para recusar uma inscrição, prefira <strong>indeferir</strong>: fica registrado o motivo.</p>
                                <x-botao type="submit" variante="perigo" tamanho="pequeno">Excluir a chapa</x-botao>
                            </form>
                        </details>
                    </div>
                @endunless
            </x-cartao>
        @empty
            <x-cartao>
                <x-estado-vazio icone="people" titulo="{{ $filtro ? 'Nenhuma chapa '.\Illuminate\Support\Str::lower($filtro->rotulo()) : 'Nenhuma chapa inscrita' }}">
                    As chapas chegam pelo formulário público (<a href="{{ route('inscricao') }}">/inscricao</a>) ou pela importação de CSV.
                </x-estado-vazio>
            </x-cartao>
        @endforelse
    @endif
@endsection
