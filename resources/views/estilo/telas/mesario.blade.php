{{-- Tela de referência (dados fictícios) · Painel do mesário (H6, H9). --}}
@extends('layouts.base')

@section('titulo', 'Mesa receptora')

@section('corpo')
    <header class="dt-topo">
        <div class="container-xl d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
            <x-marca tema="escuro" />
            <div class="d-flex align-items-center gap-3">
                <span class="small" style="color: var(--dt-tema-texto-inverso-secundario)"><i class="bi bi-person-badge" aria-hidden="true"></i> Mesário: Carlos Mendes</span>
                <a class="btn btn-sm btn-contorno" href="#">Sair</a>
            </div>
        </div>
    </header>

    <main class="container-xl py-5">
        <div class="dt-cabecalho-pagina">
            <div>
                <div class="dt-sobretitulo">Eleição do Colegiado 2026.2 · <x-selo tipo="sucesso" icone="circle-fill">Em andamento</x-selo></div>
                <h1>Mesa receptora</h1>
            </div>
        </div>

        <div class="row g-3 mb-5">
            <div class="col-sm-4"><x-indicador rotulo="Comparecimento" valor="142" detalhe="de 500 aptos · 28%" :progresso="28.4" /></div>
            <div class="col-sm-4"><x-indicador rotulo="Terminais livres" valor="1" detalhe="de 4" /></div>
            <div class="col-sm-4"><x-indicador rotulo="Encerra em" valor="03:42" detalhe="às 18:00" /></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <x-cartao titulo="Liberar eleitor" subtitulo="Confira o documento antes de liberar.">
                    <form>
                        <x-campo name="matricula" label="Matrícula" mono inputmode="numeric" autofocus value="2024007" />

                        <x-alerta tipo="info" class="mb-4" titulo="Maria Oliveira">Sistemas de Informação · 4º semestre</x-alerta>

                        <div class="mb-4">
                            <label class="form-label" for="terminal">Terminal</label>
                            <select id="terminal" class="form-select form-select-lg">
                                <option>Terminal 1 · Livre</option>
                                <option disabled>Terminal 2 · Liberado</option>
                                <option disabled>Terminal 3 · Votando</option>
                            </select>
                        </div>

                        <x-botao type="submit" tamanho="grande" icone="unlock" class="w-100">Liberar no terminal 1</x-botao>
                    </form>
                </x-cartao>

                <x-alerta tipo="perigo" titulo="Exemplo de bloqueio: eleitor já votou" class="mt-4">
                    A matrícula <span class="dt-mono">2024011</span> votou às 10:42.
                </x-alerta>
            </div>

            <div class="col-lg-7">
                <h2 class="h5 mb-3">Terminais</h2>
                <div class="row g-3">
                    <div class="col-md-6"><x-status-terminal numero="1" estado="livre" detalhe="Pronto para liberar" /></div>
                    <div class="col-md-6">
                        <x-status-terminal numero="2" estado="liberado" detalhe="João da Silva · expira em 28 min">
                            <x-botao variante="perigo" tamanho="pequeno" class="mt-3">Cancelar sessão</x-botao>
                        </x-status-terminal>
                    </div>
                    <div class="col-md-6"><x-status-terminal numero="3" estado="votando" detalhe="Eleitor na cabine" /></div>
                    <div class="col-md-6"><x-status-terminal numero="4" estado="inativo" detalhe="Desligado" /></div>
                </div>
            </div>
        </div>
    </main>
@endsection
