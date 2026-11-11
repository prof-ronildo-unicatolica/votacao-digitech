<?php

namespace App\Support;

use RuntimeException;

/** A lista de cargos não pode mudar agora (chapa já inscrita, ou cópia sobre lista não vazia). */
class CargosBloqueados extends RuntimeException {}
