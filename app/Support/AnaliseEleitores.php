<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * O que um CSV de eleitores faria se fosse confirmado (H4, D19). Nada aqui foi gravado.
 *
 * Cada linha: {linha, matricula, nome, nome_original, turma, email,
 *   situacao: novo | atualizar | igual (já cadastrado, nada muda),
 *   mudancas: {campo: [antes, depois]}, erros: list<{coluna: string|null, motivo: string}>}.
 */
final class AnaliseEleitores
{
    public function __construct(
        public readonly array $linhas,
        public readonly array $colunasIgnoradas,
        public readonly int $linhasEmBranco,
    ) {}

    public function validas(): array
    {
        return array_values(array_filter($this->linhas, fn (array $l) => $l['erros'] === []));
    }

    public function comErro(): array
    {
        return array_values(array_filter($this->linhas, fn (array $l) => $l['erros'] !== []));
    }

    public function com(string $situacao): array
    {
        return array_values(array_filter($this->validas(), fn (array $l) => $l['situacao'] === $situacao));
    }

    /** Nomes que o importador padronizou ("ANA LIMA" → "Ana Lima"). */
    public function ajustes(): array
    {
        return array_values(array_filter($this->linhas, fn (array $l) => $l['nome'] !== '' && $l['nome'] !== Str::squish($l['nome_original'])));
    }

    /** Muda se o arquivo ou o cadastro mudar entre a conferência e a confirmação (como em AnaliseImportacao). */
    public function assinatura(): string
    {
        return hash('sha256', json_encode([
            array_map(fn (array $l) => [$l['linha'], $l['matricula'], $l['situacao'], $l['mudancas'], $l['nome'], $l['turma'], $l['email']], $this->validas()),
            array_column($this->comErro(), 'linha'),
        ]));
    }
}
