<?php

namespace App\Enums;

/** Situação da inscrição (D14). Toda chapa entra pendente; a comissão homologa ou indefere (H3). */
enum SituacaoChapa: string
{
    case Pendente = 'pendente';
    case Homologada = 'homologada';
    case Indeferida = 'indeferida';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Homologada => 'Homologada',
            self::Indeferida => 'Indeferida',
        };
    }

    /** Tipo do componente <x-selo>. */
    public function selo(): string
    {
        return match ($this) {
            self::Pendente => 'aviso',
            self::Homologada => 'sucesso',
            self::Indeferida => 'perigo',
        };
    }
}
