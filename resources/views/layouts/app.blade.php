<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Votação DigiTech')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">Votação DigiTech</a>
            <div class="navbar-nav">
                <a class="nav-link" href="{{ route('mesario') }}">Mesário</a>
                <a class="nav-link" href="{{ route('urna', 1) }}">Urna</a>
                <a class="nav-link" href="{{ route('admin') }}">Admin</a>
            </div>
        </div>
    </nav>
    <main class="container">
        @yield('conteudo')
    </main>
    <footer class="container text-muted small mt-5 mb-3">
        Laboratório DIGITECH — UniCatólica · ambiente: {{ app()->environment() }}
    </footer>
</body>
</html>
