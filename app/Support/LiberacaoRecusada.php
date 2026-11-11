<?php

namespace App\Support;

use RuntimeException;

/** A liberação (ou o cancelamento) não pôde ser feita. A mensagem é para o mesário. */
class LiberacaoRecusada extends RuntimeException {}
