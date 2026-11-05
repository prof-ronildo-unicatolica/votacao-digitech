<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleicao extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'eleicoes';

    protected $guarded = [];

    public function chapas(): HasMany
    {
        return $this->hasMany(Chapa::class)->orderBy('numero');
    }

    /** Cargos que toda chapa precisa preencher, na ordem da cédula (D13). */
    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class)->orderBy('ordem');
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class);
    }

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class);
    }
}
