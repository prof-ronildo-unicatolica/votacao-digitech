<?php

namespace App\Support;

use App\Enums\AcaoAuditoria;
use App\Enums\Perfil;
use App\Models\Eleicao;
use App\Models\EventoAuditoria;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * O único ponto de escrita da auditoria (H11, D22, docs/AUDITORIA.md).
 *
 * - `registrar`: chamado pela classe de regra DENTRO da transação da ação. Se a ação for
 *   desfeita, o evento some com ela.
 * - `registrarTentativa`: para tentativas recusadas (liberação barrada), chamado DEPOIS que a
 *   transação foi desfeita: a tentativa aconteceu, mesmo sem efeito.
 * - Quem fez e de onde vêm do contexto (usuário logado, IP da requisição), salvo `$usuario`.
 */
class Auditoria
{
    /** Chaves que nunca podem aparecer num evento de sessão: ligariam o eleitor ao voto (D3). */
    private const PROIBIDAS_EM_SESSAO = ['chapa', 'chapa_id', 'chapa_numero', 'voto', 'voto_id', 'branco', 'nulo'];

    public static function registrar(
        AcaoAuditoria $acao,
        ?Model $alvo = null,
        array $dados = [],
        ?string $eleicaoId = null,
        ?User $usuario = null,
    ): EventoAuditoria {
        if ($acao->categoria() === 'sessao' && array_intersect(array_keys($dados), self::PROIBIDAS_EM_SESSAO) !== []) {
            throw new InvalidArgumentException('Evento de sessão não pode guardar a escolha do eleitor (D3, D22).');
        }

        $usuario ??= auth()->user();

        // Fase 4 (D27): cada evento entra na corrente de hashes, travando a cabeça.
        return CorrenteDeHashes::gravar(new EventoAuditoria([
            'ocorrido_em' => now(),
            'acao' => $acao,
            'user_id' => $usuario?->getKey(),
            'ator' => $usuario ? $usuario->name.' ('.($usuario->perfil === Perfil::Admin ? 'admin' : 'mesário').')' : 'Visitante',
            'ip' => request()?->ip(),
            'eleicao_id' => $eleicaoId ?? ($alvo instanceof Eleicao ? $alvo->getKey() : $alvo?->getAttribute('eleicao_id')),
            'alvo_tipo' => $alvo ? self::tipo($alvo) : null,
            'alvo_id' => $alvo ? (string) $alvo->getKey() : null,
            'dados' => $dados,
        ]));
    }

    /** Mesmo registro, para deixar claro no código que a ação foi recusada e desfeita. */
    public static function registrarTentativa(AcaoAuditoria $acao, array $dados = [], ?string $eleicaoId = null, ?User $usuario = null): EventoAuditoria
    {
        return self::registrar($acao, null, $dados, $eleicaoId, $usuario);
    }

    /** Campos que nunca entram em `dados`, nem como "alterado" (D22, princípio 5). */
    private const NUNCA = ['password', 'remember_token', 'token_hash', 'created_at', 'updated_at'];

    /**
     * O que mudou no último `save()` do model: `campo => ['antes' => ..., 'depois' => ...]`.
     * Chame logo depois do `update()`. Datas vão em ISO 8601; senha e tokens nunca entram.
     *
     * @return array<string, array{antes: mixed, depois: mixed}>
     */
    public static function alteracoes(Model $model): array
    {
        $antes = $model->getPrevious();
        $mudancas = [];
        foreach (array_keys($model->getChanges()) as $campo) {
            if (in_array($campo, self::NUNCA, true)) {
                continue;
            }
            $mudancas[$campo] = [
                'antes' => self::valor($model, $campo, $antes[$campo] ?? null),
                'depois' => self::valor($model, $campo, $model->getAttributes()[$campo] ?? null),
            ];
        }

        return $mudancas;
    }

    /**
     * Os campos escolhidos do model, como estão agora, prontos para `dados` (datas em ISO 8601).
     *
     * @param  list<string>  $campos
     */
    public static function campos(Model $model, array $campos): array
    {
        return collect($campos)->mapWithKeys(fn (string $c) => [$c => self::valor($model, $c, $model->getAttributes()[$c] ?? null)])->all();
    }

    /**
     * Valor bruto para JSON, passando pelos casts do model (o número vindo do formulário é texto,
     * o do banco é inteiro): datas em ISO 8601, enums pelo valor.
     */
    private static function valor(Model $model, string $campo, mixed $bruto): mixed
    {
        if ($bruto === null) {
            return null;
        }
        $copia = $model->newInstance();
        $copia->setRawAttributes([$campo => $bruto]);
        $valor = $copia->getAttribute($campo);

        return match (true) {
            $valor instanceof CarbonInterface => $valor->toIso8601String(),
            $valor instanceof \BackedEnum => $valor->value,
            default => $valor,
        };
    }

    private static function tipo(Model $alvo): string
    {
        return match (class_basename($alvo)) {
            'SessaoVotacao' => 'sessao',
            'User' => 'usuario',
            default => strtolower(class_basename($alvo)),
        };
    }
}
