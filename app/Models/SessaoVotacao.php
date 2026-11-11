<?php

namespace App\Models;

use App\Enums\SituacaoSessao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Assinatura na ata": a liberação de um eleitor por um mesário, num terminal (H6, D21).
 * Uma linha por eleitor e eleição, com o estado atual; o histórico é da auditoria (H11).
 */
class SessaoVotacao extends Model
{
    use HasFactory, HasUuids;

    /** Quanto tempo o eleitor tem para votar depois de liberado. */
    public const VALIDADE_MINUTOS = 30;

    protected $table = 'sessoes_votacao';

    protected $guarded = [];

    protected $attributes = ['situacao' => 'aberta'];

    protected function casts(): array
    {
        return [
            'situacao' => SituacaoSessao::class,
            'liberada_em' => 'datetime',
            'expira_em' => 'datetime',
            'exibida_em' => 'datetime',
            'votou_em' => 'datetime',
            'cancelada_em' => 'datetime',
        ];
    }

    /** Abertas e dentro do prazo: o terminal está ocupado e o eleitor pode votar. */
    public function scopeEmAndamento(Builder $query): Builder
    {
        return $query->where('situacao', SituacaoSessao::Aberta)->where('expira_em', '>', now());
    }

    /** A situação de agora: aberta com o prazo vencido é expirada. */
    public function situacaoAtual(): SituacaoSessao
    {
        if ($this->situacao === SituacaoSessao::Aberta && (! $this->expira_em || $this->expira_em->isPast())) {
            return SituacaoSessao::Expirada;
        }

        return $this->situacao;
    }

    public function emAndamento(): bool
    {
        return $this->situacaoAtual() === SituacaoSessao::Aberta;
    }

    /** Minutos que faltam (arredondado para cima), ou 0. */
    public function minutosRestantes(): int
    {
        return $this->emAndamento() ? (int) ceil(now()->diffInSeconds($this->expira_em) / 60) : 0;
    }

    /**
     * Identifica ESTA liberação para a urna (H7): muda se a sessão for cancelada e liberada de
     * novo, mesmo que o eleitor seja o mesmo. Não revela nada do eleitor.
     */
    public function versao(): string
    {
        return substr(hash('sha256', $this->id.'|'.$this->liberada_em?->format('Uu')), 0, 16);
    }

    public function eleicao(): BelongsTo
    {
        return $this->belongsTo(Eleicao::class);
    }

    public function eleitor(): BelongsTo
    {
        return $this->belongsTo(Eleitor::class);
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(Terminal::class);
    }

    public function mesario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mesario_id');
    }
}
