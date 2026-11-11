<?php

use App\Http\Controllers\Admin\EleicaoController;
use App\Http\Controllers\Admin\EleitorController;
use App\Http\Controllers\Admin\PainelController;
use App\Http\Controllers\Admin\TerminalController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Dev\RecarregarController;
use App\Http\Controllers\EstiloController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TerminalAtivacaoController;
use App\Http\Controllers\UrnaController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// H1 · Login e logout. O limite de tentativas erradas fica em App\Http\Requests\LoginRequest.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Urna (H7/H8): o eleitor não faz login. Sem ID na URL: o terminal vem do cookie de
// pareamento, conferido pelo middleware `terminal` (D16). Grupo `web`, que lê cookies.
Route::middleware('terminal')->group(function () {
    Route::get('/urna', UrnaController::class)->name('urna');
    Route::get('/urna/status', [UrnaController::class, 'status'])->name('urna.status');
});

// Área logada: `ativo` derruba quem foi desativado; `perfil` devolve 403 a quem não pode.
Route::middleware(['auth', 'ativo'])->group(function () {
    // Mesário (H6, H9). O admin também entra, para acompanhar a mesa.
    Route::view('/mesario', 'placeholders.mesario')->middleware('perfil:mesario,admin')->name('mesario');

    // H5 · Pareamento: aberto NO COMPUTADOR DA URNA. Ativar desloga este navegador (D16).
    Route::middleware('perfil:mesario,admin')->group(function () {
        Route::get('/terminal/ativar', [TerminalAtivacaoController::class, 'create'])->name('terminal.ativar');
        Route::post('/terminal/ativar', [TerminalAtivacaoController::class, 'store'])->name('terminal.ativar.store');
    });

    Route::middleware('perfil:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', PainelController::class)->name('painel');

        // H2 · Eleições
        Route::resource('eleicoes', EleicaoController::class)
            ->except('show')
            ->parameters(['eleicoes' => 'eleicao']);
        Route::post('eleicoes/{eleicao}/ativar', [EleicaoController::class, 'ativar'])->name('eleicoes.ativar');
        Route::post('eleicoes/{eleicao}/desativar', [EleicaoController::class, 'desativar'])->name('eleicoes.desativar');

        // H4 · Eleitores (um a um ou CSV). As rotas de importar vêm antes do resource.
        Route::get('eleitores/importar', [EleitorController::class, 'importarForm'])->name('eleitores.importar');
        Route::post('eleitores/importar', [EleitorController::class, 'importar'])->name('eleitores.importar.store');
        Route::get('eleitores/modelo.csv', [EleitorController::class, 'modelo'])->name('eleitores.modelo');
        Route::resource('eleitores', EleitorController::class)
            ->except('show')
            ->parameters(['eleitores' => 'eleitor']);

        // H5 · Terminais. Ativar é em /terminal/ativar; aqui o admin só desativa.
        Route::resource('terminais', TerminalController::class)
            ->except('show')
            ->parameters(['terminais' => 'terminal']);
        Route::post('terminais/{terminal}/desativar', [TerminalController::class, 'desativar'])->name('terminais.desativar');

        // H1 · Usuários (admin e mesário)
        Route::resource('usuarios', UsuarioController::class)->except('show');
        Route::post('usuarios/{usuario}/ativo', [UsuarioController::class, 'alternarAtivo'])->name('usuarios.ativo');
    });
});

// Vitrine da identidade visual (docs/IDENTIDADE_VISUAL.md), com dados fictícios. Fora de produção.
if (! app()->isProduction()) {
    Route::get('/estilo', [EstiloController::class, 'index'])->name('estilo');
    Route::get('/estilo/telas/{tela}', [EstiloController::class, 'tela'])
        ->whereIn('tela', array_keys(EstiloController::TELAS))
        ->name('estilo.tela');
}

// Auto-refresh do navegador ao salvar arquivos (só em desenvolvimento local, sem Node).
if (app()->isLocal()) {
    Route::get('/_dev/recarregar', RecarregarController::class)->name('dev.recarregar');
}
