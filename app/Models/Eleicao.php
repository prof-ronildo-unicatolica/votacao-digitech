<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleicao extends Model
{
    use HasFactory;

    protected $table = 'eleicoes';

    protected $guarded = [];

    public function chapas(): HasMany
    {
        return $this->hasMany(Chapa::class)->orderBy('numero');
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
