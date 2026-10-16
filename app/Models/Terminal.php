<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Terminal extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'terminais';

    protected $guarded = [];

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }
}
