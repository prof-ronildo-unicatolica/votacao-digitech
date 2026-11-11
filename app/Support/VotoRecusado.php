<?php

namespace App\Support;

use RuntimeException;

/** O voto não foi registrado. A mensagem é para o eleitor na urna e nunca cita a escolha. */
class VotoRecusado extends RuntimeException {}
