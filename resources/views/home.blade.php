{{--
    Página inicial pública. A eleição ativa, a fase dela, o que fazer agora e as chapas homologadas.
    Sem eleição ativa, só o aviso e o acesso da comissão.
--}}
@extends('layouts.app')

@section('titulo', $eleicao?->titulo ?? 'Início')

@use('App\Enums\FaseEleicao')

@php
    $formato = 'd/m/Y \à\s H:i';
    // Etapa atual na linha do tempo: 0 inscrições, 1 homologação, 2 votação, 3 resultado; -1 antes de tudo.
    $atual = match ($fase) {
        FaseEleicao::InscricoesAbertas => 0,
        FaseEleicao::EmHomologacao => 1,
        FaseEleicao::VotacaoAberta => 2,
        FaseEleicao::Encerrada => 3,
        default => -1,
    };
    $etapas = $eleicao ? [
        ['Inscrição de chapas', 'pencil-square', 'de '.$eleicao->inscricao_abre_em?->format('d/m H:i').' a '.$eleicao->inscricao_fecha_em?->format('d/m H:i')],
        ['Homologação', 'clipboard-check', 'a comissão analisa as chapas'],
        ['Votação', 'check2-square', 'de '.$eleicao->votacao_abre_em?->format('d/m H:i').' a '.$eleicao->votacao_fecha_em?->format('d/m H:i')],
        ['Resultado', 'bar-chart', 'divulgado pela comissão'],
    ] : [];
@endphp

@section('conteudo')
    <div class="dt-inicio mx-auto">
        @if (! $eleicao)
            <div class="dt-cabecalho-pagina">
                <div>
                    <div class="dt-sobretitulo">Votação DigiTech</div>
                    <h1>Eleições do curso</h1>
                </div>
            </div>
            <x-cartao>
                <x-estado-vazio icone="calendar" titulo="Nenhuma eleição em andamento">
                    Quando a comissão eleitoral abrir uma eleição, as datas e as chapas aparecem aqui.
                </x-estado-vazio>
            </x-cartao>
        @else
            <div class="dt-cabecalho-pagina">
                <div>
                    <div class="dt-sobretitulo">Eleição em andamento</div>
                    <h1>{{ $eleicao->titulo }}</h1>
                </div>
                @if ($fase)
                    <x-selo :tipo="$fase->selo()" :icone="$fase->icone()">{{ $fase->rotulo() }}</x-selo>
                @endif
            </div>

            {{-- O que fazer agora --}}
            <section class="dt-cartao dt-inicio-agora mb-5" aria-labelledby="agora-titulo">
                <div class="dt-cartao-corpo">
                    @switch($fase)
                        @case(FaseEleicao::AguardandoInscricoes)
                            <h2 id="agora-titulo">As inscrições de chapas ainda não abriram</h2>
                            <p>Abrem em <strong>{{ $eleicao->inscricao_abre_em->format($formato) }}</strong> e vão até {{ $eleicao->inscricao_fecha_em->format($formato) }}.</p>
                            @break
                        @case(FaseEleicao::InscricoesAbertas)
                            <h2 id="agora-titulo">Inscrições de chapas abertas</h2>
                            <p>Até <strong>{{ $eleicao->inscricao_fecha_em->format($formato) }}</strong>. Cada chapa indica um aluno para cada cargo, pela matrícula, e passa pela análise da comissão eleitoral.</p>
                            <x-botao :href="route('inscricao')" icone="pencil-square" tamanho="grande">Inscrever uma chapa</x-botao>
                            @break
                        @case(FaseEleicao::EmHomologacao)
                            <h2 id="agora-titulo">Inscrições encerradas</h2>
                            <p>A comissão eleitoral está analisando as chapas. A votação abre em <strong>{{ $eleicao->votacao_abre_em->format($formato) }}</strong>.</p>
                            @break
                        @case(FaseEleicao::VotacaoAberta)
                            <h2 id="agora-titulo">Votação aberta</h2>
                            <p>Até <strong>{{ $eleicao->votacao_fecha_em->format($formato) }}</strong>. Vá até a mesa receptora com um documento com foto.</p>
                            @break
                        @case(FaseEleicao::Encerrada)
                            <h2 id="agora-titulo">Votação encerrada</h2>
                            <p>A votação terminou em <strong>{{ $eleicao->votacao_fecha_em->format($formato) }}</strong>. O resultado é divulgado pela comissão eleitoral.</p>
                            @break
                        @default
                            <h2 id="agora-titulo">Datas a definir</h2>
                            <p>A comissão eleitoral ainda vai divulgar as datas de inscrição e de votação.</p>
                    @endswitch
                </div>
            </section>

            {{-- Linha do tempo --}}
            @if ($fase)
                <section class="mb-5" aria-labelledby="calendario-titulo">
                    <h2 id="calendario-titulo" class="dt-inicio-secao">Calendário</h2>
                    <ol class="dt-etapas">
                        @foreach ($etapas as $i => [$rotulo, $icone, $detalhe])
                            @php($estado = $i < $atual ? 'feita' : ($i === $atual ? 'atual' : 'futura'))
                            <li class="dt-etapa dt-etapa--{{ $estado }}" @if ($estado === 'atual') aria-current="step" @endif>
                                <span class="dt-etapa-icone" aria-hidden="true"><i class="bi bi-{{ $estado === 'feita' ? 'check-lg' : $icone }}"></i></span>
                                <span class="dt-etapa-rotulo">{{ $rotulo }}</span>
                                <span class="dt-etapa-detalhe">{{ $detalhe }}</span>
                                @if ($estado === 'atual')<span class="visually-hidden">(fase atual)</span>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- Como votar: aparece da homologação em diante, quando já interessa a todo eleitor --}}
            @if (in_array($fase, [FaseEleicao::EmHomologacao, FaseEleicao::VotacaoAberta], true))
                <section class="mb-5" aria-labelledby="como-votar-titulo">
                    <h2 id="como-votar-titulo" class="dt-inicio-secao">Como votar</h2>
                    <div class="row g-3">
                        @foreach ([
                            ['person-vcard', 'Na mesa', 'Mostre um documento com foto. O mesário confere sua matrícula e libera uma urna.'],
                            ['hand-index', 'Na urna', 'Escolha uma chapa, branco ou nulo. Confira e confirme. Dá para corrigir antes de confirmar.'],
                            ['shield-lock', 'Voto secreto', 'O sistema registra que você votou, mas não guarda em quem. Cada aluno vota uma vez.'],
                        ] as [$icone, $titulo, $texto])
                            <div class="col-md-4">
                                <div class="dt-cartao h-100">
                                    <div class="dt-cartao-corpo">
                                        <i class="bi bi-{{ $icone }} dt-inicio-passo-icone" aria-hidden="true"></i>
                                        <h3 class="h6 mt-3 mb-1">{{ $titulo }}</h3>
                                        <p class="small text-secondary mb-0">{{ $texto }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Chapas homologadas (as que vão para a urna) --}}
            <section class="mb-5" aria-labelledby="chapas-titulo">
                <h2 id="chapas-titulo" class="dt-inicio-secao">Chapas homologadas</h2>
                @forelse ($chapas as $chapa)
                    <article class="dt-cartao dt-inicio-chapa mb-3" aria-label="Chapa {{ $chapa->numero }}: {{ $chapa->nome }}">
                        <div class="dt-inicio-chapa-numero" aria-hidden="true">{{ $chapa->numero }}</div>
                        <div>
                            <h3 class="h5 mb-1">{{ $chapa->nome }}</h3>
                            <dl class="dt-inicio-composicao mb-0">
                                @foreach ($chapa->composicao as $candidato)
                                    <div><dt>{{ $candidato->cargo->titulo }}</dt><dd>{{ $candidato->eleitor->nome }}</dd></div>
                                @endforeach
                            </dl>
                        </div>
                    </article>
                @empty
                    <x-cartao>
                        <x-estado-vazio icone="people" titulo="Nenhuma chapa homologada ainda">
                            As chapas aparecem aqui depois que a comissão eleitoral aprova a inscrição.
                        </x-estado-vazio>
                    </x-cartao>
                @endforelse
            </section>
        @endif

        {{-- Acesso restrito --}}
        <section class="dt-inicio-acesso" aria-labelledby="acesso-titulo">
            <h2 id="acesso-titulo" class="h6 mb-1">Comissão eleitoral e mesários</h2>
            @auth
                <p class="small text-secondary mb-3">Você entrou como {{ auth()->user()->name }}.</p>
                <x-botao :href="auth()->user()->paginaInicial()" variante="contorno" icone="arrow-right">{{ auth()->user()->isAdmin() ? 'Ir para o painel' : 'Ir para a mesa' }}</x-botao>
            @else
                <p class="small text-secondary mb-3">Entre com o e-mail e a senha cadastrados pela comissão.</p>
                <x-botao :href="route('login')" variante="contorno" icone="box-arrow-in-right">Entrar</x-botao>
            @endauth
            <p class="small text-secondary mt-3 mb-0">
                Computador de votação? Um mesário ativa a urna em <a href="{{ route('terminal.ativar') }}">{{ route('terminal.ativar', absolute: false) }}</a>.
            </p>
        </section>
    </div>
@endsection
