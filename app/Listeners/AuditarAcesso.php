<?php

namespace App\Listeners;

use App\Enums\AcaoAuditoria;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Str;

/**
 * H11 · Eventos de acesso (D22): login, tentativa recusada, bloqueio e logout. Vêm dos eventos
 * do próprio Laravel, então a regra do login (LoginRequest) não muda. Senha nunca é registrada.
 */
class AuditarAcesso
{
    public function login(Login $evento): void
    {
        Auditoria::registrar(AcaoAuditoria::Login, $evento->user, usuario: $evento->user);
    }

    public function falhou(Failed $evento): void
    {
        $usuario = $evento->user;

        Auditoria::registrarTentativa(AcaoAuditoria::LoginFalhou, [
            'email' => Str::lower((string) ($evento->credentials['email'] ?? '')),
            'motivo' => match (true) {
                ! $usuario => 'usuario_inexistente',
                $usuario instanceof User && ! $usuario->ativo => 'usuario_desativado',
                default => 'senha_incorreta',
            },
        ]);
    }

    public function bloqueado(Lockout $evento): void
    {
        Auditoria::registrarTentativa(AcaoAuditoria::AcessoBloqueado, [
            'email' => Str::lower((string) $evento->request->input('email')),
        ]);
    }

    public function logout(Logout $evento): void
    {
        if ($evento->user) {
            Auditoria::registrar(AcaoAuditoria::Logout, $evento->user, usuario: $evento->user);
        }
    }
}
