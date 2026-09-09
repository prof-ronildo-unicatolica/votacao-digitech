<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleitor extends Model
{
    use HasFactory;

    protected $table = 'eleitores';

    protected $guarded = [];

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }
}
