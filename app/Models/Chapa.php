<?php

namespace App\Models;

use App\Enums\OrigemChapa;
use App\Enums\SituacaoChapa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Chapa inscrita numa eleição (D14). Só homologada vai para a urna e para a apuração. */
class Chapa extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $attributes = ['situacao' => 'pendente', 'origem' => 'formulario'];

    protected function casts(): array
    {
        return ['situacao' => SituacaoChapa::class, 'origem' => OrigemChapa::class];
    }

    public function scopeHomologadas(Builder $query): Builder
    {
        return $query->where('situacao', SituacaoChapa::Homologada);
    }

    public function eleicao(): BelongsTo
    {
        return $this->belongsTo(Eleicao::class);
    }

    /** Composição da chapa: um candidato por cargo da eleição (D13). */
    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class);
    }

    /** Composição na ordem da cédula (cargo 1, 2, 3...), com o aluno de cada cargo. */
    public function composicao(): HasMany
    {
        return $this->candidatos()
            ->join('cargos', 'cargos.id', '=', 'candidatos.cargo_id')
            ->orderBy('cargos.ordem')
            ->select('candidatos.*')
            ->with(['cargo', 'eleitor']);
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class);
    }
}
