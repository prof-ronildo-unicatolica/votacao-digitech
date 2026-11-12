<?php

namespace App\Support;

use App\Enums\AcaoAuditoria;
use App\Models\Eleicao;
use App\Models\EventoAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Os filtros da tela de auditoria (H11 fase 3), lidos da URL. A tela e o CSV usam a mesma
 * consulta, para o arquivo exportado ser exatamente o que estava na tela.
 *
 * - `eleicao`: a ativa por padrão; `todas` tira o filtro (acesso e usuários não têm eleição).
 * - `grupo` e `acao`: do catálogo (`AcaoAuditoria`). Valores fora dele são ignorados.
 * - `usuario`, `de` e `ate` (datas, dia inteiro), `busca` (matrícula, número, e-mail... dentro
 *   de `dados`), `alvo_tipo` e `alvo_id` (atalho "Histórico" da chapa).
 */
final class FiltroAuditoria
{
    /** @param array<string, string> $valores os filtros válidos, como estão na URL */
    private function __construct(public readonly array $valores) {}

    public static function daRequisicao(Request $request): self
    {
        $q = fn (string $chave) => trim((string) $request->query($chave));
        $data = fn (string $chave) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $q($chave)) ? $q($chave) : '';

        $eleicao = $q('eleicao');
        if ($eleicao === '') {
            $eleicao = Eleicao::atual()?->id ?? 'todas';
        }

        return new self(array_filter([
            'eleicao' => $eleicao,
            'grupo' => array_key_exists($q('grupo'), AcaoAuditoria::GRUPOS) ? $q('grupo') : '',
            'acao' => AcaoAuditoria::tryFrom($q('acao'))?->value ?? '',
            'usuario' => ctype_digit($q('usuario')) ? $q('usuario') : '',
            'de' => $data('de'),
            'ate' => $data('ate'),
            'busca' => mb_substr($q('busca'), 0, 60),
            'alvo_tipo' => preg_match('/^[a-z]{1,20}$/', $q('alvo_tipo')) ? $q('alvo_tipo') : '',
            'alvo_id' => mb_substr($q('alvo_id'), 0, 36),
        ], fn ($v) => $v !== ''));
    }

    public function get(string $chave): string
    {
        return $this->valores[$chave] ?? '';
    }

    /** Mais recentes primeiro. */
    public function consulta(): Builder
    {
        $f = $this->valores;

        return EventoAuditoria::query()
            ->when(($f['eleicao'] ?? 'todas') !== 'todas', fn (Builder $q) => $q->where('eleicao_id', $f['eleicao']))
            ->when($f['grupo'] ?? null, fn (Builder $q, $g) => $q->whereIn('acao', array_map(fn ($a) => $a->value, AcaoAuditoria::doGrupo($g))))
            ->when($f['acao'] ?? null, fn (Builder $q, $a) => $q->where('acao', $a))
            ->when($f['usuario'] ?? null, fn (Builder $q, $u) => $q->where('user_id', (int) $u))
            ->when($f['de'] ?? null, fn (Builder $q, $d) => $q->where('ocorrido_em', '>=', Carbon::parse($d)->startOfDay()))
            ->when($f['ate'] ?? null, fn (Builder $q, $d) => $q->where('ocorrido_em', '<=', Carbon::parse($d)->endOfDay()))
            ->when($f['busca'] ?? null, fn (Builder $q, $b) => $q->where(fn (Builder $q) => $q
                ->where('dados', 'like', '%'.self::escapar($b).'%')
                ->orWhere('ator', 'like', '%'.self::escapar($b).'%')))
            ->when($f['alvo_tipo'] ?? null, fn (Builder $q, $t) => $q->where('alvo_tipo', $t))
            ->when($f['alvo_id'] ?? null, fn (Builder $q, $id) => $q->where('alvo_id', $id))
            ->orderByDesc('ocorrido_em');
    }

    /** `%` e `_` digitados são texto, não curinga. */
    private static function escapar(string $termo): string
    {
        return addcslashes($termo, '%_\\');
    }
}
