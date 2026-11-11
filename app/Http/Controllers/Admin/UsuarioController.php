<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Perfil;
use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $usuario = User::create($request->dados());

        return redirect()->route('admin.usuarios.index')->with('status', "{$usuario->name} cadastrado como {$usuario->perfil->rotulo()}.");
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.form', ['usuario' => $usuario]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $usuario->update($request->dados());

        return redirect()->route('admin.usuarios.index')->with('status', "{$usuario->name} atualizado.");
    }

    /** Desativar não apaga: o usuário só deixa de entrar (e sai na próxima requisição). */
    public function alternarAtivo(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['geral' => 'Você não pode desativar o seu próprio usuário.']);
        }

        $usuario->update(['ativo' => ! $usuario->ativo]);

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

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')->with('status', "{$usuario->name} foi excluído.");
    }
}
