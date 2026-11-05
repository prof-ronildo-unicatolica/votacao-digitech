<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Cargo que toda chapa da eleição precisa preencher (uma vaga por chapa). */
class Cargo extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function eleicao(): BelongsTo
    {
        return $this->belongsTo(Eleicao::class);
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class);
    }
}
