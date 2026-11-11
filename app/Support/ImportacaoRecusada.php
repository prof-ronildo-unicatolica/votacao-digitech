<?php

namespace App\Support;

use RuntimeException;

/** A confirmação da importação não pôde seguir (ex.: há erros e o admin não escolheu ignorá-los). Nada foi gravado. */
class ImportacaoRecusada extends RuntimeException {}
