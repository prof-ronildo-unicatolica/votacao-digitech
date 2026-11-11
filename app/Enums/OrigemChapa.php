<?php

namespace App\Enums;

/** Por onde a chapa foi inscrita (D14): formulário público (H14) ou CSV da comissão (H15). */
enum OrigemChapa: string
{
    case Formulario = 'formulario';
    case Csv = 'csv';

    public function rotulo(): string
    {
        return match ($this) {
            self::Formulario => 'Formulário',
            self::Csv => 'CSV',
        };
    }
}
