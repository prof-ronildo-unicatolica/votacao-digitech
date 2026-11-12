<?php

namespace App\Console\Commands;

use App\Support\CorrenteDeHashes;
use Illuminate\Console\Command;

/**
 * H11 fase 4 · Recalcula a corrente de hashes da auditoria (D27) e aponta o primeiro evento
 * alterado, apagado ou inserido por fora da aplicação. Sai com código 1 se algo não conferir.
 *
 *   php artisan digitech:auditoria:verificar
 */
class VerificarAuditoria extends Command
{
    protected $signature = 'digitech:auditoria:verificar';

    protected $description = 'Confere a corrente de hashes da auditoria';

    public function handle(): int
    {
        $resultado = CorrenteDeHashes::verificar();

        if (! $resultado['ok']) {
            $this->error('A auditoria NÃO confere. '.$resultado['problema']);
            if ($resultado['evento']) {
                $this->line("Evento: {$resultado['evento']}");
            }
            $this->line("Eventos conferidos antes do problema: {$resultado['eventos']}.");

            return self::FAILURE;
        }

        $cabeca = CorrenteDeHashes::cabeca();
        $this->info("A auditoria confere: {$resultado['eventos']} eventos na corrente.");
        $this->line('Último hash: '.($cabeca->ultimo_hash ?? '(corrente vazia)'));

        return self::SUCCESS;
    }
}
