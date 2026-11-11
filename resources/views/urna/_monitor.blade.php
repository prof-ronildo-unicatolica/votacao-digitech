{{--
    Polling da urna (H7): a cada 3 s pergunta ao servidor se a liberação deste terminal mudou e,
    se mudou (liberou, cancelou, expirou, liberou outra pessoa), recarrega a página. A tela em si é
    sempre montada no servidor. Sem cookie válido (403), recarrega e mostra "não ativado".
--}}
@if (request()->routeIs('urna')) {{-- não na vitrine /estilo, que não tem cookie de terminal --}}
@push('scripts')
    <script>
        (() => {
            const versaoNaTela = @json($versao ?? null);
            const url = @json(route('urna.status'));
            const consultar = async () => {
                try {
                    const resposta = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                    if (resposta.status === 403) return location.reload();
                    if (resposta.ok) {
                        const status = await resposta.json();
                        if ((status.liberada ? status.versao : null) !== versaoNaTela) return location.reload();
                    }
                } catch (erro) {
                    // Rede instável: tenta de novo na próxima volta, sem mudar a tela.
                }
                setTimeout(consultar, 3000);
            };
            setTimeout(consultar, 3000);
        })();
    </script>
@endpush
@endif
