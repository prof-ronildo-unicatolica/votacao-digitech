<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Terminal extends Model
{
    use HasFactory;

    protected $table = 'terminais';

    protected $fillable = ['numero', 'nome'];

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }

    /** Sessão liberada e ainda válida neste terminal, se houver. */
    public function sessaoAberta(): HasOne
    {
        return $this->hasOne(SessaoVotacao::class)
            ->where('status', 'aberta')
            ->where('expira_em', '>', now())
            ->latestOfMany('liberada_em');
    }
}
