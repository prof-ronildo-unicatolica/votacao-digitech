<?php

namespace App\Enums;

/** O que é um voto (H8, D24): numa chapa, em branco ou nulo. Branco e nulo não têm chapa. */
enum TipoVoto: string
{
    case Chapa = 'chapa';
    case Branco = 'branco';
    case Nulo = 'nulo';

    public function rotulo(): string
    {
        return match ($this) {
            self::Chapa => 'Chapa',
            self::Branco => 'Branco',
            self::Nulo => 'Nulo',
        };
    }
}
