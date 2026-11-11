<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pareamento do computador da urna (H5, D16). Admin ou mesário, logado NO COMPUTADOR DA URNA,
 * escolhe qual terminal ele é. O navegador recebe o cookie e é deslogado na mesma resposta,
 * para o eleitor nunca encontrar uma sessão de mesário aberta.
 */
class TerminalAtivacaoController extends Controller
{
    public function create(): View
    {
        return view('terminal.ativar', ['terminais' => Terminal::orderBy('numero')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate(
            ['terminal_id' => ['required', 'uuid', 'exists:terminais,id']],
            ['terminal_id.required' => 'Escolha qual terminal é este computador.'],
            ['terminal_id' => 'terminal'],
        );

        $token = Terminal::findOrFail($dados['terminal_id'])->ativar();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('urna')->withCookie(cookie(
            name: Terminal::COOKIE,
            value: $token,
            minutes: Terminal::VALIDADE_HORAS * 60,
            secure: config('session.secure') ?? $request->isSecure(),
            httpOnly: true,
            sameSite: 'strict',
        ));
    }
}
