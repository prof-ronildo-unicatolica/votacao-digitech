<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Voto anônimo: nunca adicione eleitor_id, terminal_id ou sessao_id aqui.
 * O teste tests/Feature/SigiloDoVotoTest.php garante isso.
 */
class Voto extends Model
{
    public $timestamps = false;

    protected $fillable = ['eleicao_id', 'chapa_id', 'tipo', 'registrado_em'];

    protected function casts(): array
    {
        return ['registrado_em' => 'datetime'];
    }

    public function eleicao(): BelongsTo
    {
        return $this->belongsTo(Eleicao::class);
    }

    public function chapa(): BelongsTo
    {
        return $this->belongsTo(Chapa::class);
    }
}
