{{--
    Inscrição pública de chapa (H14, D14). Sem login; a maioria abre pelo celular.
    POST /inscricao → App\Http\Controllers\InscricaoController (regras em App\Support\InscricaoDeChapa).
    O script no fim só confere o nome pela matrícula; sem JavaScript o formulário funciona igual.
--}}
@extends('layouts.app')

@section('titulo', 'Inscrição de chapa')

@section('conteudo')
    <div style="max-width: 640px" class="mx-auto">
        <div class="dt-cabecalho-pagina">
            <div>
                <div class="dt-sobretitulo">{{ $eleicao->titulo }}</div>
                <h1>Inscrição de chapa</h1>
            </div>
        </div>

        <x-alerta tipo="info" class="mb-4">
            Inscrições abertas até <strong>{{ $eleicao->inscricao_fecha_em->format('d/m/Y \à\s H:i') }}</strong>.
            A chapa precisa de um candidato para cada um dos {{ $cargos->count() }} cargos.
            Toda inscrição entra como <strong>pendente</strong> e passa pela comissão eleitoral.
        </x-alerta>

        @error('geral')
            <x-alerta tipo="perigo" class="mb-4">{{ $message }}</x-alerta>
        @enderror

        <form method="POST" action="{{ route('inscricao.store') }}" novalidate>
            @csrf

            <x-cartao titulo="Chapa" class="mb-4">
                <x-campo name="nome" label="Nome da chapa" maxlength="100" required autocomplete="off" />
                <x-campo name="contato_email" type="email" label="E-mail do responsável" required autocomplete="email"
                    ajuda="A comissão usa este contato para falar sobre a inscrição. Não aparece em nenhuma página pública." />
                <x-campo name="contato_telefone" type="tel" label="Telefone do responsável" autocomplete="tel" ajuda="Opcional. Ex.: (88) 99999-0000." />
            </x-cartao>

            <x-cartao titulo="Candidatos" subtitulo="A matrícula de um aluno para cada cargo" class="mb-4">
                @foreach ($cargos as $cargo)
                    @php
                        $campo = "candidatos.{$cargo->id}";
                        $id = "matricula-{$cargo->ordem}";
                        $valor = old($campo);
                        $nome = is_string($valor) ? ($nomes[trim($valor)] ?? null) : null;
                    @endphp
                    <div class="mb-4">
                        <label for="{{ $id }}" class="form-label">{{ $cargo->ordem }}. {{ $cargo->titulo }}</label>
                        <input type="text" id="{{ $id }}" name="candidatos[{{ $cargo->id }}]" value="{{ $valor }}" maxlength="20"
                            autocomplete="off" required placeholder="Matrícula" aria-describedby="{{ $id }}-nome"
                            data-conferir-matricula
                            @class(['form-control', 'form-control-lg', 'dt-mono', 'is-invalid' => $errors->has($campo)])>
                        @error($campo)
                            <div class="invalid-feedback"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
                        @enderror
                        <div id="{{ $id }}-nome" class="form-text" aria-live="polite">
                            @if ($nome && ! $errors->has($campo))<i class="bi bi-person-check" aria-hidden="true"></i> {{ $nome }}@endif
                        </div>
                    </div>
                @endforeach
            </x-cartao>

            <x-botao type="submit" icone="send" class="w-100 mb-5">Enviar inscrição</x-botao>
        </form>
    </div>

    <script>
        // Confere o nome do aluno ao sair do campo de matrícula (H14). Só leitura: quem decide é o servidor no envio.
        document.querySelectorAll('[data-conferir-matricula]').forEach((campo) => {
            const saida = document.getElementById(campo.getAttribute('aria-describedby'));
            let ultima = campo.value.trim();

            campo.addEventListener('change', async () => {
                const matricula = campo.value.trim();
                if (matricula === ultima) return;
                ultima = matricula;
                saida.textContent = '';
                if (matricula === '') return;

                try {
                    const resposta = await fetch(@json(route('inscricao.aluno')) + '?matricula=' + encodeURIComponent(matricula), { headers: { Accept: 'application/json' } });
                    if (resposta.status === 429) { saida.textContent = 'Muitas consultas seguidas. Aguarde um pouco.'; return; }
                    const dados = await resposta.json();
                    saida.textContent = dados.encontrado ? '✓ ' + dados.nome : 'Matrícula não encontrada.';
                } catch {
                    saida.textContent = '';
                }
            });
        });
    </script>
@endsection
