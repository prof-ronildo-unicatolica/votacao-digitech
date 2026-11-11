<?php

namespace App\Models;

use App\Enums\AcaoAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Um evento de auditoria (H11, D22). Só acrescenta: alterar ou apagar lança exceção.
 * Grave sempre por App\Support\Auditoria, nunca com create() direto.
 */
class EventoAuditoria extends Model
{
    use HasUuids;

    protected $table = 'auditoria';

    public $timestamps = false;

    /** Microssegundos: eventos do mesmo segundo (login e logout, liberar e recusar) ficam em ordem. */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['hash'];

    protected function casts(): array
    {
        return ['acao' => AcaoAuditoria::class, 'dados' => 'array', 'ocorrido_em' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Evento de auditoria não pode ser alterado (D22).'));
        static::deleting(fn () => throw new LogicException('Evento de auditoria não pode ser apagado (D22).'));
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function eleicao(): BelongsTo
    {
        return $this->belongsTo(Eleicao::class);
    }
}
