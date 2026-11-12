<?php

namespace App\Support;

use InvalidArgumentException;

/** O arquivo inteiro não serve (cabeçalho, colunas de cargo, tamanho). Nenhuma linha é conferida. */
class ArquivoInvalido extends InvalidArgumentException
{
    /** @param list<string> $erros */
    public function __construct(public readonly array $erros)
    {
        parent::__construct(implode(' ', $erros));
    }
}
