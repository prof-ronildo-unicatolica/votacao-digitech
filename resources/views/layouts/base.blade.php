{{-- Esqueleto de todas as páginas: fontes, Bootstrap, ícones e a identidade visual (docs/IDENTIDADE_VISUAL.md). --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#18181A">
    <title>@hasSection('titulo')@yield('titulo') · @endif Votação DigiTech</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('img/marca/favicon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/marca/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/tokens.css') }}" rel="stylesheet">
    <link href="{{ asset('css/digitech.css') }}" rel="stylesheet">
    @stack('head')
</head>
<body @yield('body-atributos')>
    @yield('corpo')
    @stack('scripts')
    @if (app()->isLocal())
        {{-- Auto-refresh de desenvolvimento: recarrega quando um arquivo observado muda. --}}
        <script>
            (() => {
                let versao = null;
                setInterval(async () => {
                    try {
                        const r = await fetch(@json(route('dev.recarregar')), { cache: 'no-store' });
                        const { versao: nova } = await r.json();
                        if (versao !== null && nova !== versao) location.reload();
                        versao = nova;
                    } catch (e) { /* servidor reiniciando: tenta de novo no próximo ciclo */ }
                }, 1000);
            })();
        </script>
    @endif
</body>
</html>
