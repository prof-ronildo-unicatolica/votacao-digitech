<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleicao extends Model
{
    use HasFactory;

    protected $table = 'eleicoes';

    protected $fillable = ['titulo', 'inicio', 'fim', 'ativa'];

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fim' => 'datetime',
            'ativa' => 'boolean',
        ];
    }

    public function chapas(): HasMany
    {
        return $this->hasMany(Chapa::class)->orderBy('numero');
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class);
    }

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }

    /** Eleição marcada como ativa (no máximo uma por vez, por convenção). */
    public static function ativa(): ?self
    {
        return static::where('ativa', true)->latest('inicio')->first();
    }

    public function votacaoAberta(): bool
    {
        return $this->ativa && now()->between($this->inicio, $this->fim);
    }
}
