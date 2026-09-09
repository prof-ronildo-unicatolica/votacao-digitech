<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleitor extends Model
{
    use HasFactory;

    protected $table = 'eleitores';

    protected $fillable = ['matricula', 'nome', 'email', 'turma'];

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }

    public function jaVotou(Eleicao $eleicao): bool
    {
        return $this->sessoes()
            ->where('eleicao_id', $eleicao->id)
            ->where('status', 'votou')
            ->exists();
    }
}
