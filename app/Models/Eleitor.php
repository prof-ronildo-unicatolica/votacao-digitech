<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Aluno apto a votar em toda eleição (D4, D17). Não faz login; é identificado pela matrícula. */
class Eleitor extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'eleitores';

    protected $guarded = [];

    /** Em que chapa/cargo o aluno concorre (no máximo uma candidatura por eleição). */
    public function candidaturas(): HasMany
    {
        return $this->hasMany(Candidato::class);
    }

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }

    /** Busca da lista do admin: matrícula pelo começo ou parte do nome. */
    public function scopeBusca(Builder $query, ?string $termo): Builder
    {
        $termo = trim((string) $termo);

        if ($termo === '') {
            return $query;
        }

        $like = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $termo);

        return $query->where(fn (Builder $q) => $q
            ->where('matricula', 'like', "{$like}%")
            ->orWhere('nome', 'like', "%{$like}%"));
    }

    /**
     * Por que este eleitor não pode ser excluído, ou null se pode.
     * O banco também bloqueia (RESTRICT em `sessoes_votacao` e `candidatos`).
     */
    public function motivoParaNaoExcluir(): ?string
    {
        if ($this->sessoes()->exists()) {
            return "{$this->nome} já foi liberado para votar e faz parte da ata: não pode ser excluído.";
        }

        if ($this->candidaturas()->exists()) {
            return "{$this->nome} é candidato em uma chapa: tire o aluno da chapa antes de excluí-lo.";
        }

        return null;
    }
}
