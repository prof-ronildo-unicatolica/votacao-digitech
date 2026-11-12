<?php

namespace App\Enums;

/** Perfis de quem faz login (D4: eleitor não é usuário). Gravado em users.perfil. */
enum Perfil: string
{
    case Admin = 'admin';
    case Mesario = 'mesario';

    public function rotulo(): string
    {
        return match ($this) {
            self::Admin => 'Admin (comissão)',
            self::Mesario => 'Mesário',
        };
    }
}
