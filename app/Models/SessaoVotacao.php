<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessaoVotacao extends Model
{
    use HasFactory;

    protected $table = 'sessoes_votacao';

    protected $guarded = [];

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
