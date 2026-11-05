<?php

namespace App\Models;

use App\Enums\FaseEleicao;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Eleicao extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'eleicoes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'inscricao_abre_em' => 'datetime',
            'inscricao_fecha_em' => 'datetime',
            'votacao_abre_em' => 'datetime',
            'votacao_fecha_em' => 'datetime',
        ];
    }

    /**
     * `ativa` é TRUE ou NULL no banco, nunca FALSE: o UNIQUE da coluna aceita vários NULL e
     * um único TRUE, garantindo "só uma eleição ativa" (D15). No PHP é sempre bool.
     */
    protected function ativa(): Attribute
    {
        return Attribute::make(
            get: fn ($valor) => (bool) $valor,
            set: fn ($valor) => $valor ? true : null,
        );
    }

    /** A eleição ativa (no máximo uma, garantido pelo banco), ou null. */
    public static function atual(): ?self
    {
        return static::query()->where('ativa', true)->first();
    }

    /** Torna esta a eleição ativa, desativando a anterior na mesma transação (H2). */
    public function ativar(): void
    {
        DB::transaction(function () {
            static::query()->whereKeyNot($this->getKey())->where('ativa', true)->update(['ativa' => null]);
            $this->update(['ativa' => true]);
        });
    }

    public function desativar(): void
    {
        $this->update(['ativa' => false]);
    }

    /** Fase da eleição agora (ou no momento informado). Null se faltar alguma data. */
    public function fase(?CarbonInterface $momento = null): ?FaseEleicao
    {
        if (! $this->inscricao_abre_em || ! $this->inscricao_fecha_em || ! $this->votacao_abre_em || ! $this->votacao_fecha_em) {
            return null;
        }

        return FaseEleicao::em(
            $momento ?? now(),
            $this->inscricao_abre_em,
            $this->inscricao_fecha_em,
            $this->votacao_abre_em,
            $this->votacao_fecha_em,
        );
    }

    /** Já tem chapa inscrita? Depois disso o período de inscrição só pode ser prorrogado (H2). */
    public function temInscricoes(): bool
    {
        return $this->chapas()->exists();
    }

    public function chapas(): HasMany
    {
        return $this->hasMany(Chapa::class)->orderBy('numero');
    }

    /** Cargos que toda chapa precisa preencher, na ordem da cédula (D13). */
    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class)->orderBy('ordem');
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class);
    }

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class);
    }
}
