<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Voto anônimo: nunca crie relação com Eleitor, Terminal ou SessaoVotacao aqui.
 */
class Voto extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    /** Sem created_at/updated_at: o horário do voto permitiria cruzar com sessoes_votacao. */
    public $timestamps = false;

    /**
     * UUID v4 ALEATÓRIO. O padrão do HasUuids é ordenado por tempo (v7), o que
     * vazaria a ordem dos votos, quebrando o sigilo.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function eleicao(): BelongsTo
    {
        return $this->belongsTo(Eleicao::class);
    }

    public function chapa(): BelongsTo
    {
        return $this->belongsTo(Chapa::class);
    }
}
