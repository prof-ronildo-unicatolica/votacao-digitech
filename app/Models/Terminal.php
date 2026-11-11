<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Computador de votação. Só funciona depois de pareado (D16): o navegador guarda um token
 * no cookie `terminal` e o banco guarda só o hash dele.
 */
class Terminal extends Model
{
    use HasFactory, HasUuids;

    /** Por quanto tempo um pareamento vale (cobre um dia de votação). */
    public const VALIDADE_HORAS = 24;

    /** Nome do cookie de pareamento (criptografado pelo Laravel, HttpOnly). */
    public const COOKIE = 'terminal';

    protected $table = 'terminais';

    protected $guarded = [];

    /** O hash nunca vai para JSON, nem por engano. */
    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['ativado_em' => 'datetime'];
    }

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoVotacao::class);
    }

    /** Terminal pareado com o token do cookie, se o pareamento ainda vale. */
    public static function porToken(?string $token): ?self
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        return static::where('token_hash', self::hash($token))
            ->where('ativado_em', '>', now()->subHours(self::VALIDADE_HORAS))
            ->first();
    }

    /**
     * Pareia este terminal com um novo computador e devolve o token para o cookie.
     * Substituir o hash invalida o computador que estava pareado antes.
     */
    public function ativar(): string
    {
        $token = Str::random(64);

        $this->forceFill(['token_hash' => self::hash($token), 'ativado_em' => now()])->save();

        return $token;
    }

    public function desativar(): void
    {
        $this->forceFill(['token_hash' => null, 'ativado_em' => null])->save();
    }

    public function estaAtivado(): bool
    {
        return $this->token_hash !== null && $this->expiraEm()?->isFuture();
    }

    public function expiraEm(): ?Carbon
    {
        return $this->ativado_em?->copy()->addHours(self::VALIDADE_HORAS);
    }

    /** A sessão aberta e dentro do prazo neste terminal, se houver (H6). */
    public function sessaoEmAndamento(): HasOne
    {
        return $this->hasOne(SessaoVotacao::class)->emAndamento(); // no máximo uma: a liberação trava o terminal
    }

    /**
     * Estado mostrado ao admin e ao mesário (componente x-status-terminal): inativo (não
     * pareado), liberado (tem sessão em andamento) ou livre. "Votando" depende da urna (H7).
     */
    public function estado(): string
    {
        if (! $this->estaAtivado()) {
            return 'inativo';
        }

        return $this->sessaoEmAndamento ? 'liberado' : 'livre';
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
