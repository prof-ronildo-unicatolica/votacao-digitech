{{--
    Vitrine da identidade visual. Tudo aqui usa os componentes reais de resources/views/components.
    Para levar ao Figma: extensão html.to.design nesta página (docs/FIGMA.md).
--}}
@extends('layouts.app')

@section('titulo', 'Identidade visual')

@section('conteudo')
    <div class="dt-cabecalho-pagina">
        <div>
            <div class="dt-sobretitulo">Design system · v1</div>
            <h1>Identidade visual</h1>
            <p class="text-secondary mb-0" style="max-width: 62ch">
                Marca, cores, tipografia e componentes da Votação DigiTech. Fonte única:
                <code>design/tokens/tokens.json</code>. Guia completo em <code>docs/IDENTIDADE_VISUAL.md</code>.
            </p>
        </div>
    </div>

    <nav class="d-flex flex-wrap gap-2 mb-7" aria-label="Seções">
        @foreach (['marca' => 'Marca', 'cores' => 'Cores', 'tipografia' => 'Tipografia', 'componentes' => 'Componentes', 'urna' => 'Urna', 'telas' => 'Telas'] as $ancora => $rotulo)
            <a class="btn btn-contorno btn-sm" href="#{{ $ancora }}">{{ $rotulo }}</a>
        @endforeach
    </nav>

    {{-- MARCA --}}
    <section id="marca" class="dt-secao">
        <h2>Marca</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="dt-cartao p-5 d-flex align-items-center justify-content-center" style="min-height: 180px">
                    <img src="{{ asset('img/marca/unicatolica-logo-cor.png') }}" alt="Logotipo UniCatólica em cores" style="max-width: 100%; width: 360px">
                </div>
                <div class="small text-secondary mt-2">Logotipo institucional · sobre fundo claro</div>
            </div>
            <div class="col-md-6">
                <div class="p-5 d-flex align-items-center justify-content-center" style="min-height: 180px; background: var(--dt-tema-superficie-escura); border-radius: var(--dt-raio-md)">
                    <img src="{{ asset('img/marca/unicatolica-logo-branco.png') }}" alt="Logotipo UniCatólica em branco" style="max-width: 100%; width: 360px">
                </div>
                <div class="small text-secondary mt-2">Logotipo negativo · sobre grafite 900</div>
            </div>
            <div class="col-md-6">
                <div class="dt-cartao p-5 d-flex align-items-center justify-content-center" style="min-height: 140px">
                    <x-marca tamanho="grande" />
                </div>
                <div class="small text-secondary mt-2">Assinatura do produto <code>&lt;x-marca /&gt;</code></div>
            </div>
            <div class="col-md-6">
                <div class="p-5 d-flex align-items-center justify-content-center" style="min-height: 140px; background: var(--dt-tema-superficie-escura); border-radius: var(--dt-raio-md)">
                    <x-marca tema="escuro" tamanho="grande" />
                </div>
                <div class="small text-secondary mt-2">Assinatura do produto <code>&lt;x-marca tema="escuro" /&gt;</code></div>
            </div>
        </div>
    </section>

    {{-- CORES --}}
    <section id="cores" class="dt-secao">
        <h2>Cores</h2>
        @foreach ($escalas as $familia => $passos)
            <h3 class="h6 text-capitalize mb-3">{{ $familia }}</h3>
            <div class="dt-grade-amostras mb-4">
                @foreach ($passos as $p)
                    @php($textoClaro = $p['contrasteBranco'] >= $p['contrastePreto'])
                    <div>
                        <div class="dt-amostra">
                            <div class="dt-amostra-cor" style="background: {{ $p['hex'] }}; color: {{ $textoClaro ? '#FFFFFF' : '#18181A' }}">
                                Aa {{ number_format(max($p['contrasteBranco'], $p['contrastePreto']), 1, ',', '') }}
                            </div>
                            <div class="dt-amostra-legenda"><strong>{{ $p['token'] }}</strong><br><code>{{ $p['hex'] }}</code></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <h3 class="h6 mb-3">Semânticas</h3>
        <div class="row g-3 mb-4">
            @foreach ($semanticas as $papel => $c)
                <div class="col-sm-6 col-lg-3">
                    <div class="dt-amostra">
                        <div class="dt-amostra-cor" style="background: {{ $c['fundo'] }}; color: {{ $c['texto'] }}; border-bottom: 3px solid {{ $c['borda'] }}">
                            {{ ucfirst($papel) }}
                        </div>
                        <div class="dt-amostra-legenda">texto <code>{{ $c['texto'] }}</code> · fundo <code>{{ $c['fundo'] }}</code></div>
                    </div>
                </div>
            @endforeach
        </div>

        <h3 class="h6 mb-1">Dados — chapas no gráfico de apuração</h3>
        <p class="small text-secondary">Pela ordem do número da chapa. O laranja da marca nunca é cor de chapa.</p>
        <div class="dt-grade-amostras">
            @foreach ($dados as $p)
                <div>
                    <div class="dt-amostra">
                        <div class="dt-amostra-cor" style="background: {{ $p['hex'] }}"></div>
                        <div class="dt-amostra-legenda"><strong>{{ $p['token'] }}</strong><br><code>{{ $p['hex'] }}</code></div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- TIPOGRAFIA --}}
    <section id="tipografia" class="dt-secao">
        <h2>Tipografia</h2>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="dt-cartao dt-cartao-corpo">
                    @foreach (['5xl' => '56 · Número', '4xl' => '40 · Título da urna', '3xl' => '32 · Título de página', '2xl' => '24 · Seção / mínimo na urna', 'xl' => '20 · Título de cartão'] as $t => $uso)
                        <div class="d-flex align-items-baseline gap-3 py-2 border-bottom">
                            <span class="small text-secondary" style="width: 11rem; flex-shrink: 0">{{ $uso }}</span>
                            <span style="font-family: var(--dt-fonte-familia-titulo); font-weight: 600; font-size: var(--dt-fonte-tamanho-{{ $t }}); line-height: 1.1">Eleição 2026</span>
                        </div>
                    @endforeach
                    @foreach (['lg' => '18 · Destaque', 'md' => '16 · Texto', 'sm' => '14 · Auxiliar', 'xs' => '12 · Legenda'] as $t => $uso)
                        <div class="d-flex align-items-baseline gap-3 py-2 border-bottom">
                            <span class="small text-secondary" style="width: 11rem; flex-shrink: 0">{{ $uso }}</span>
                            <span style="font-size: var(--dt-fonte-tamanho-{{ $t }})">O mesário confere a matrícula e libera o terminal.</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-5 d-grid gap-3">
                <div class="dt-cartao dt-cartao-corpo">
                    <div class="small text-secondary">Títulos</div>
                    <div style="font-family: var(--dt-fonte-familia-titulo); font-size: 2rem; font-weight: 600">Poppins</div>
                    <div class="small text-secondary">500 · 600 · 700 — a fonte do site da UniCatólica</div>
                </div>
                <div class="dt-cartao dt-cartao-corpo">
                    <div class="small text-secondary">Texto e interface</div>
                    <div style="font-size: 2rem; font-weight: 600">Inter</div>
                    <div class="small text-secondary dt-numeros">400 · 500 · 600 · 700 — números tabulares: 1.234 / 0.987</div>
                </div>
                <div class="dt-cartao dt-cartao-corpo">
                    <div class="small text-secondary">Matrícula e códigos</div>
                    <div class="dt-mono" style="font-size: 2rem">2024007</div>
                    <div class="small text-secondary">JetBrains Mono — 0 ≠ O, 1 ≠ l</div>
                </div>
            </div>
        </div>
    </section>

    {{-- COMPONENTES --}}
    <section id="componentes" class="dt-secao">
        <h2>Componentes</h2>

        <h3 class="h6 mb-3">Botões <code class="small">&lt;x-botao&gt;</code></h3>
        <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
            <x-botao icone="unlock">Primário</x-botao>
            <x-botao variante="secundario">Secundário</x-botao>
            <x-botao variante="contorno" icone="download">Contorno</x-botao>
            <x-botao variante="fantasma">Fantasma</x-botao>
            <x-botao variante="perigo" icone="x-circle">Perigo</x-botao>
            <x-botao disabled>Desabilitado</x-botao>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-center mb-6">
            <x-botao tamanho="grande">Grande · 48px</x-botao>
            <x-botao>Padrão · 44px</x-botao>
            <x-botao tamanho="pequeno" variante="contorno">Pequeno · 32px</x-botao>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-6">
                <h3 class="h6 mb-3">Campos <code class="small">&lt;x-campo&gt;</code></h3>
                <x-cartao>
                    <x-campo name="matricula" label="Matrícula" mono inputmode="numeric" placeholder="Ex.: 2024007" ajuda="Digite só os números." />
                    <div class="mb-4">
                        <label class="form-label" for="exemplo-terminal">Terminal</label>
                        <select id="exemplo-terminal" class="form-select form-select-lg">
                            <option>Terminal 1 · Livre</option>
                            <option disabled>Terminal 2 · Ocupado</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="exemplo-erro">Com erro</label>
                        <input id="exemplo-erro" class="form-control form-control-lg dt-mono is-invalid" value="2024999" aria-describedby="exemplo-erro-msg">
                        <div id="exemplo-erro-msg" class="invalid-feedback"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Matrícula não encontrada nesta eleição.</div>
                    </div>
                </x-cartao>
            </div>
            <div class="col-lg-6">
                <h3 class="h6 mb-3">Alertas <code class="small">&lt;x-alerta&gt;</code></h3>
                <div class="d-grid gap-3">
                    <x-alerta tipo="sucesso" titulo="Eleitor liberado">Maria Oliveira pode votar no terminal 3.</x-alerta>
                    <x-alerta tipo="aviso" titulo="Sessão expira em 2 minutos">Se o eleitor não votar, libere de novo.</x-alerta>
                    <x-alerta tipo="perigo" titulo="Eleitor já votou">A matrícula 2024007 votou às 10:42 no terminal 2.</x-alerta>
                    <x-alerta tipo="info">A eleição encerra às 18:00.</x-alerta>
                </div>
            </div>
        </div>

        <h3 class="h6 mb-3">Selos <code class="small">&lt;x-selo&gt;</code></h3>
        <div class="d-flex flex-wrap gap-2 mb-6">
            <x-selo tipo="sucesso" icone="circle-fill">Em andamento</x-selo>
            <x-selo tipo="aviso" icone="clock">Agendada</x-selo>
            <x-selo icone="lock">Encerrada</x-selo>
            <x-selo tipo="perigo" icone="x-circle">Cancelada</x-selo>
            <x-selo tipo="info">Votando</x-selo>
            <x-selo tipo="marca">Ativa</x-selo>
        </div>

        <h3 class="h6 mb-3">Indicadores <code class="small">&lt;x-indicador&gt;</code></h3>
        <div class="row g-3 mb-6">
            <div class="col-sm-6 col-lg-3"><x-indicador rotulo="Comparecimento" valor="142" detalhe="de 500 aptos · 28%" :progresso="28.4" /></div>
            <div class="col-sm-6 col-lg-3"><x-indicador rotulo="Terminais livres" valor="2" detalhe="de 4" /></div>
            <div class="col-sm-6 col-lg-3"><x-indicador rotulo="Chapas" valor="3" /></div>
            <div class="col-sm-6 col-lg-3"><x-indicador rotulo="Encerra em" valor="03:42" detalhe="às 18:00" /></div>
        </div>

        <h3 class="h6 mb-3">Terminais <code class="small">&lt;x-status-terminal&gt;</code></h3>
        <div class="row g-3 mb-6">
            <div class="col-sm-6 col-lg-3"><x-status-terminal numero="1" estado="livre" detalhe="Pronto para liberar" /></div>
            <div class="col-sm-6 col-lg-3">
                <x-status-terminal numero="2" estado="liberado" detalhe="Maria Oliveira · expira em 28 min">
                    <x-botao variante="perigo" tamanho="pequeno" class="mt-3">Cancelar sessão</x-botao>
                </x-status-terminal>
            </div>
            <div class="col-sm-6 col-lg-3"><x-status-terminal numero="3" estado="votando" detalhe="Eleitor na cabine" /></div>
            <div class="col-sm-6 col-lg-3"><x-status-terminal numero="4" estado="inativo" detalhe="Desligado" /></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <h3 class="h6 mb-3">Cartão + tabela</h3>
                <x-cartao titulo="Eleitores" subtitulo="500 aptos nesta eleição" sem-padding>
                    <x-slot:acoes><x-botao variante="contorno" tamanho="pequeno" icone="upload">Importar CSV</x-botao></x-slot:acoes>
                    <table class="table dt-tabela">
                        <thead><tr><th>Matrícula</th><th>Nome</th><th>Turma</th><th class="text-end">Situação</th></tr></thead>
                        <tbody>
                            <tr><td class="dt-mono">2024007</td><td>Maria Oliveira</td><td>SI · 4º</td><td class="text-end"><x-selo tipo="sucesso">Votou</x-selo></td></tr>
                            <tr><td class="dt-mono">2024008</td><td>João da Silva</td><td>ADS · 2º</td><td class="text-end"><x-selo>Não votou</x-selo></td></tr>
                            <tr><td class="dt-mono">2024009</td><td>Ana Beatriz Lima</td><td>SI · 6º</td><td class="text-end"><x-selo tipo="info">Votando</x-selo></td></tr>
                        </tbody>
                    </table>
                </x-cartao>
            </div>
            <div class="col-lg-5">
                <h3 class="h6 mb-3">Estado vazio</h3>
                <x-cartao>
                    <x-estado-vazio icone="people" titulo="Nenhum eleitor cadastrado">
                        Importe a lista da secretaria em CSV para começar.
                        <div class="mt-3"><x-botao icone="upload">Importar CSV</x-botao></div>
                    </x-estado-vazio>
                </x-cartao>
            </div>
        </div>
    </section>

    {{-- URNA --}}
    <section id="urna" class="dt-secao">
        <h2>Urna <code class="small">&lt;x-opcao-chapa&gt;</code></h2>
        <p class="text-secondary">Todas as chapas com o mesmo peso visual, na ordem do número. Selecione para ver o estado marcado; navegue com Tab e setas.</p>
        <div class="p-4 p-md-5" style="background: var(--dt-tema-superficie-escura); border-radius: var(--dt-raio-lg)">
            <div class="dt-urna-cartao mx-auto" style="font-size: var(--dt-fonte-tamanho-2xl)">
                <fieldset class="dt-opcoes">
                    <legend class="visually-hidden">Escolha uma opção</legend>
                    <x-opcao-chapa name="exemplo" numero="10" nome="Inovação Acadêmica" descricao="Presidente: Ana Lima · Vice: Pedro Souza" checked />
                    <x-opcao-chapa name="exemplo" numero="20" nome="Movimento Estudantil" descricao="Presidente: Carla Dias · Vice: Rafael Nunes" />
                    <x-opcao-chapa name="exemplo" nome="Branco" descricao="Não escolher nenhuma chapa" :neutra="true" />
                </fieldset>
            </div>
        </div>
    </section>

    {{-- TELAS --}}
    <section id="telas" class="dt-secao">
        <h2>Telas de referência</h2>
        <p class="text-secondary">Composições com dados fictícios, a partir dos rascunhos da equipe. Abra cada uma e importe no Figma.</p>
        <div class="row g-3">
            @foreach ($telas as $chave => $nome)
                <div class="col-sm-6 col-lg-3">
                    <a class="dt-cartao d-flex align-items-center justify-content-between p-3 text-decoration-none text-reset h-100" href="{{ route('estilo.tela', $chave) }}">
                        <span class="fw-semibold">{{ $nome }}</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endsection
