<?php

namespace App\Support;

use App\Models\EventoAuditoria;
use Illuminate\Support\Facades\DB;

/**
 * H11 fase 4 · Corrente de hashes da auditoria (D27, docs/AUDITORIA.md seção 7).
 *
 * Cada evento guarda `hash = SHA-256(hash do anterior | conteúdo canônico deste)`. Alterar,
 * apagar ou inserir uma linha por fora da aplicação quebra a corrente a partir dela, e
 * `verificar()` aponta o primeiro evento que não confere.
 *
 * - **Detecta, não impede.** A proteção de verdade é o acesso restrito ao banco em produção.
 * - **Fora do conteúdo:** `user_id` e `eleicao_id`. São vínculos que o próprio banco anula
 *   quando o usuário ou a eleição são excluídos (`nullOnDelete`). Quem fez continua protegido
 *   pelo `ator`, e o alvo pelo `alvo_tipo`/`alvo_id`.
 */
final class CorrenteDeHashes
{
    /** Dá ao evento (ainda não gravado) a próxima posição e o hash, e grava. Trava a cabeça. */
    public static function gravar(EventoAuditoria $evento): EventoAuditoria
    {
        return DB::transaction(function () use ($evento) {
            $cabeca = DB::table('auditoria_cabeca')->where('id', 1)->lockForUpdate()->first();

            $evento->id ??= $evento->newUniqueId();
            $evento->forceFill(['sequencia' => $cabeca->ultima_sequencia + 1]);
            $evento->forceFill(['hash' => self::hash($cabeca->ultimo_hash, $evento)]);
            $evento->save();

            DB::table('auditoria_cabeca')->where('id', 1)->update([
                'ultima_sequencia' => $evento->sequencia,
                'ultimo_hash' => $evento->hash,
            ]);

            return $evento;
        });
    }

    public static function hash(?string $anterior, EventoAuditoria $evento): string
    {
        return hash('sha256', ($anterior ?? '').'|'.self::conteudo($evento));
    }

    /** O evento em JSON com as chaves em ordem: o mesmo texto ao gravar e ao reler do banco. */
    public static function conteudo(EventoAuditoria $evento): string
    {
        return json_encode([
            'sequencia' => (int) $evento->sequencia,
            'id' => (string) $evento->id,
            'ocorrido_em' => $evento->ocorrido_em->format('Y-m-d H:i:s.u'),
            'acao' => $evento->acao->value,
            'ator' => $evento->ator,
            'ip' => $evento->ip,
            'alvo_tipo' => $evento->alvo_tipo,
            'alvo_id' => $evento->alvo_id,
            'dados' => self::ordenar($evento->dados ?? []),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    /** O último elo, para imprimir na ata (âncora fora do banco). */
    public static function cabeca(): object
    {
        return DB::table('auditoria_cabeca')->where('id', 1)->first();
    }

    /**
     * Recalcula a corrente inteira.
     *
     * @return array{ok: bool, eventos: int, problema: string|null, evento: string|null}
     */
    public static function verificar(): array
    {
        $anterior = null;
        $esperada = 1;
        $falha = fn (string $problema, ?string $evento = null) => ['ok' => false, 'eventos' => $esperada - 1, 'problema' => $problema, 'evento' => $evento];

        foreach (EventoAuditoria::whereNotNull('sequencia')->lazyById(500, 'sequencia') as $evento) {
            if ((int) $evento->sequencia !== $esperada) {
                return $falha("Falta o evento de posição {$esperada} (apagado por fora da aplicação?). O seguinte é o de posição {$evento->sequencia}.", $evento->id);
            }
            if (! hash_equals((string) $evento->hash, self::hash($anterior, $evento))) {
                return $falha("O evento de posição {$esperada} não confere: foi alterado por fora da aplicação.", $evento->id);
            }
            $anterior = $evento->hash;
            $esperada++;
        }

        $cabeca = self::cabeca();
        if ((int) $cabeca->ultima_sequencia !== $esperada - 1 || $cabeca->ultimo_hash !== $anterior) {
            return $falha('A corrente termina na posição '.($esperada - 1).", mas a cabeça diz {$cabeca->ultima_sequencia}: eventos do fim foram apagados.");
        }

        // Evento sem posição gravado depois do início da corrente: inserido por fora.
        $primeiro = EventoAuditoria::where('sequencia', 1)->value('ocorrido_em');
        if ($primeiro && ($intruso = EventoAuditoria::whereNull('sequencia')->where('ocorrido_em', '>', $primeiro)->value('id'))) {
            return $falha('Há um evento sem posição na corrente, gravado depois do início dela: foi inserido por fora da aplicação.', $intruso);
        }

        return ['ok' => true, 'eventos' => $esperada - 1, 'problema' => null, 'evento' => null];
    }

    /** Chaves de objetos em ordem alfabética, em qualquer nível (o MySQL reordena o JSON). */
    private static function ordenar(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            return $valor;
        }
        if (! array_is_list($valor)) {
            ksort($valor, SORT_STRING);
        }

        return array_map(self::ordenar(...), $valor);
    }
}
