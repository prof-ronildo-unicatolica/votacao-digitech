<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Aluno (eleitor, pela matrícula) que ocupa um cargo em uma chapa. */
class Candidato extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function eleicao(): BelongsTo
    {
        return $this->belongsTo(Eleicao::class);
    }

    public function chapa(): BelongsTo
    {
        return $this->belongsTo(Chapa::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function eleitor(): BelongsTo
    {
        return $this->belongsTo(Eleitor::class);
    }
}
