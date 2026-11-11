<?php

namespace App\Support;

use App\Enums\AcaoAuditoria;
use App\Enums\Perfil;
use App\Models\EventoAuditoria;
use App\Models\User;
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

        return EventoAuditoria::create([
            'ocorrido_em' => now(),
            'acao' => $acao,
            'user_id' => $usuario?->getKey(),
            'ator' => $usuario ? $usuario->name.' ('.($usuario->perfil === Perfil::Admin ? 'admin' : 'mesário').')' : 'Visitante',
            'ip' => request()?->ip(),
            'eleicao_id' => $eleicaoId ?? ($alvo?->getAttribute('eleicao_id')),
            'alvo_tipo' => $alvo ? self::tipo($alvo) : null,
            'alvo_id' => $alvo ? (string) $alvo->getKey() : null,
            'dados' => $dados,
        ]);
    }

    /** Mesmo registro, para deixar claro no código que a ação foi recusada e desfeita. */
    public static function registrarTentativa(AcaoAuditoria $acao, array $dados = [], ?string $eleicaoId = null, ?User $usuario = null): EventoAuditoria
    {
        return self::registrar($acao, null, $dados, $eleicaoId, $usuario);
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
