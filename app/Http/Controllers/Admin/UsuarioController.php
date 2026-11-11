<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcaoAuditoria;
use App\Enums\Perfil;
use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Admin cadastra, edita, desativa e exclui admins e mesários (H1). */
class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::withCount('sessoesLiberadas')->orderByDesc('ativo')->orderBy('name')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => new User(['perfil' => Perfil::Mesario])]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = DB::transaction(function () use ($request) {
            $usuario = User::create($request->dados());
            Auditoria::registrar(AcaoAuditoria::UsuarioCriado, $usuario, ['email' => $usuario->email, 'perfil' => $usuario->perfil->value]);

            return $usuario;
        });

        return redirect()->route('admin.usuarios.index')->with('status', "{$usuario->name} cadastrado como {$usuario->perfil->rotulo()}.");
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.form', ['usuario' => $usuario]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        DB::transaction(function () use ($request, $usuario) {
            $usuario->update($request->dados());
            // A senha nunca entra: só o fato de ter sido trocada (D22).
            $dados = ['email' => $usuario->email, 'perfil' => $usuario->perfil->value, 'campos' => Auditoria::alteracoes($usuario)];
            if ($usuario->wasChanged('password')) {
                $dados['senha_trocada'] = true;
            }
            if ($dados['campos'] || isset($dados['senha_trocada'])) {
                Auditoria::registrar(AcaoAuditoria::UsuarioAlterado, $usuario, $dados);
            }
        });

        return redirect()->route('admin.usuarios.index')->with('status', "{$usuario->name} atualizado.");
    }

    /** Desativar não apaga: o usuário só deixa de entrar (e sai na próxima requisição). */
    public function alternarAtivo(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['geral' => 'Você não pode desativar o seu próprio usuário.']);
        }

        DB::transaction(function () use ($usuario) {
            $usuario->update(['ativo' => ! $usuario->ativo]);
            Auditoria::registrar($usuario->ativo ? AcaoAuditoria::UsuarioReativado : AcaoAuditoria::UsuarioDesativado, $usuario, ['email' => $usuario->email, 'perfil' => $usuario->perfil->value]);
        });

        return back()->with('status', $usuario->ativo ? "{$usuario->name} foi reativado." : "{$usuario->name} foi desativado e não entra mais.");
    }

    /** Quem já liberou sessões não pode ser apagado (o banco também bloqueia): desative. */
    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['geral' => 'Você não pode excluir o seu próprio usuário.']);
        }

        if ($usuario->sessoesLiberadas()->exists()) {
            return back()->withErrors(['geral' => "{$usuario->name} já liberou eleitores e faz parte da ata: não pode ser excluído. Desative em vez de excluir."]);
        }

        DB::transaction(function () use ($usuario) {
            // Antes do DELETE: depois dele, o vínculo do evento com o usuário seria anulado.
            Auditoria::registrar(AcaoAuditoria::UsuarioExcluido, $usuario, ['email' => $usuario->email, 'perfil' => $usuario->perfil->value]);
            $usuario->delete();
        });

        return redirect()->route('admin.usuarios.index')->with('status', "{$usuario->name} foi excluído.");
    }
}
