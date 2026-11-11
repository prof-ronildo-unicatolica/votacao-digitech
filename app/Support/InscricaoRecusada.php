<?php

namespace App\Support;

use RuntimeException;

/**
 * A inscrição não pôde ser gravada. `erros` tem uma mensagem por campo:
 * `geral`, `nome` ou `candidatos.{cargo_id}` (a matrícula de cada cargo).
 */
class InscricaoRecusada extends RuntimeException
{
    /** @param array<string, string> $erros */
    public function __construct(public readonly array $erros)
    {
        parent::__construct(implode(' ', $erros));
    }
}
