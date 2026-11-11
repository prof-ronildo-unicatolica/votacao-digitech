<?php

namespace App\Support;

use RuntimeException;

/** Entre a conferência e a confirmação, o resultado do arquivo mudou. A transação foi desfeita. */
class ImportacaoDesatualizada extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A situação mudou desde a conferência (por exemplo, uma chapa se inscreveu pelo formulário ou um cargo foi alterado). Nada foi gravado: confira o resultado de novo antes de confirmar.');
    }
}
