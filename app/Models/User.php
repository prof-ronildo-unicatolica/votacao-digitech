<?php

namespace App\Models;

use App\Enums\Perfil;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Quem faz login: admin (comissão eleitoral) ou mesário. Eleitor NÃO é usuário (D4). */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atributos que podem ser preenchidos em massa (create/update).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'matricula',
        'password',
        'perfil',
        'ativo',
    ];

    /**
     * Atributos ocultos ao serializar (JSON, arrays).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversões automáticas de tipo (casts).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'perfil' => Perfil::class,
            'ativo' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->perfil === Perfil::Admin;
    }

    /** Para onde o usuário vai depois de entrar (H1). */
    public function paginaInicial(): string
    {
        return $this->isAdmin() ? route('admin.painel') : route('mesario');
    }

    /** Liberações feitas por este usuário como mesário (impede apagá-lo, ver H1). */
    public function sessoesLiberadas(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class, 'mesario_id');
    }
}
