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

    /**
     * `dados` em uma linha, para a lista da tela (H11 fase 3). Ex.: "numero: 20 · titulo: A → B".
     * O JSON completo fica em "Ver detalhes".
     */
    public function resumo(): string
    {
        return collect($this->dados ?? [])
            ->map(fn ($valor, $chave) => $chave.': '.self::texto($valor))
            ->join(' · ');
    }

    private static function texto(mixed $valor): string
    {
        return match (true) {
            is_bool($valor) => $valor ? 'sim' : 'não',
            $valor === null => '—',
            is_array($valor) && array_key_exists('antes', $valor) && array_key_exists('depois', $valor) => self::texto($valor['antes']).' → '.self::texto($valor['depois']),
            is_array($valor) && array_is_list($valor) => implode(', ', array_map(self::texto(...), $valor)),
            is_array($valor) => '('.collect($valor)->map(fn ($v, $k) => $k.': '.self::texto($v))->join(', ').')',
            default => (string) $valor,
        };
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
