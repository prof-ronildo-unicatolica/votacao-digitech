<?php

namespace App\Support;

/**
 * O que um CSV de chapas faria se fosse confirmado (H15). Nada aqui foi gravado.
 *
 * - `cargos`: um por coluna de cargo, na ordem da cédula:
 *   {chave, titulo, coluna (texto original do cabeçalho), existe (já é cargo da eleição)}.
 * - `criaCargos`: a eleição ainda não tem cargos e o cabeçalho vai criá-los.
 * - `chapas`: uma por linha do arquivo:
 *   {linha, numero (int|null), numero_bruto, nome, nome_original, contato_email, contato_telefone,
 *    candidatos: {chave do cargo: {matricula, aluno (nome|null), eleitor_id (se válido)}},
 *    erros: list<{coluna: string|null (numero_chapa, nome_chapa, título do cargo, contato_*), motivo: string}>}.
 * - `ajustes`: nomes que o importador padronizou ("VICE PRESIDENTE" → "Vice-presidente").
 */
final class AnaliseImportacao
{
    public function __construct(
        public readonly array $cargos,
        public readonly bool $criaCargos,
        public readonly array $chapas,
        public readonly array $ajustes,
        public readonly int $linhasEmBranco,
    ) {}

    /** @return list<array> */
    public function validas(): array
    {
        return array_values(array_filter($this->chapas, fn (array $chapa) => $chapa['erros'] === []));
    }

    /** @return list<array> */
    public function comErro(): array
    {
        return array_values(array_filter($this->chapas, fn (array $chapa) => $chapa['erros'] !== []));
    }

    /** Colunas com erro de uma linha (para destacar a célula na tela). */
    public static function colunasComErro(array $chapa): array
    {
        return array_values(array_unique(array_filter(array_column($chapa['erros'], 'coluna'))));
    }

    /**
     * Resume o que a confirmação vai gravar. A tela de conferência manda a assinatura de volta:
     * se na hora de confirmar ela for outra (uma chapa se inscreveu pelo formulário, um aluno
     * foi excluído, os cargos mudaram), nada é gravado e o admin confere de novo.
     */
    public function assinatura(): string
    {
        return hash('sha256', json_encode([
            $this->criaCargos,
            array_column($this->cargos, 'titulo'),
            array_map(fn (array $chapa) => [$chapa['linha'], $chapa['numero'], $chapa['nome'], array_column($chapa['candidatos'], 'matricula')], $this->validas()),
            array_column($this->comErro(), 'linha'),
        ]));
    }
}
