<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Voto anônimo: nunca crie relação com Eleitor, Terminal ou SessaoVotacao aqui.
 */
class Voto extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function eleicao(): BelongsTo
    {
        return $this->belongsTo(Eleicao::class);
    }

    public function chapa(): BelongsTo
    {
        return $this->belongsTo(Chapa::class);
    }
}
