<?php

namespace App\Enums;

/**
 * Situação da sessão de votação (H6, D21). `Expirada` não é gravada no banco: é uma sessão
 * `aberta` cujo prazo passou (SessaoVotacao::situacaoAtual).
 */
enum SituacaoSessao: string
{
    case Aberta = 'aberta';
    case Votou = 'votou';
    case Expirada = 'expirada';
    case Cancelada = 'cancelada';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aberta => 'Liberado',
            self::Votou => 'Votou',
            self::Expirada => 'Expirada',
            self::Cancelada => 'Cancelada',
        };
    }

    /** Pode ser liberada de novo (re-liberação reaproveita a mesma linha). */
    public function permiteReliberar(): bool
    {
        return $this === self::Expirada || $this === self::Cancelada;
    }
}
