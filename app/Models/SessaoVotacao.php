<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessaoVotacao extends Model
{
    use HasFactory;

    public const MINUTOS_VALIDADE = 30;

    protected $table = 'sessoes_votacao';

    protected $fillable = [
        'eleicao_id', 'eleitor_id', 'terminal_id', 'liberada_por',
        'status', 'liberada_em', 'expira_em', 'votou_em',
    ];

    protected function casts(): array
    {
        return [
            'liberada_em' => 'datetime',
            'expira_em' => 'datetime',
            'votou_em' => 'datetime',
        ];
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
        return $this->belongsTo(User::class, 'liberada_por');
    }

    public function estaValida(): bool
    {
        return $this->status === 'aberta' && $this->expira_em->isFuture();
    }
}
