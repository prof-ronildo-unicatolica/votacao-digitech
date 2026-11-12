<?php

namespace App\Http\Controllers;

use App\Enums\AcaoAuditoria;
use App\Models\Terminal;
use App\Support\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Auditado ANTES do logout, para o evento ter quem ativou (D16, D22).
        $token = DB::transaction(function () use ($dados) {
            $terminal = Terminal::whereKey($dados['terminal_id'])->lockForUpdate()->firstOrFail();
            $substituiu = $terminal->estaAtivado();
            $token = $terminal->ativar();
            Auditoria::registrar(AcaoAuditoria::TerminalAtivado, $terminal, ['numero' => $terminal->numero, 'substituiu_outro_computador' => $substituiu]);

            return $token;
        });

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
