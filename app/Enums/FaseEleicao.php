<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Fase de uma eleição em um momento, calculada pelas quatro datas (H2, D14).
 * Cada período inclui o início e exclui o fim: às 18:00 de um período que fecha às 18:00
 * ele já está fechado.
 */
enum FaseEleicao: string
{
    case AguardandoInscricoes = 'aguardando_inscricoes';
    case InscricoesAbertas = 'inscricoes_abertas';
    case EmHomologacao = 'em_homologacao';
    case VotacaoAberta = 'votacao_aberta';
    case Encerrada = 'encerrada';

    /** Função pura: mesma entrada, mesma saída (alvo de teste unitário). */
    public static function em(
        CarbonInterface $momento,
        CarbonInterface $inscricaoAbre,
        CarbonInterface $inscricaoFecha,
        CarbonInterface $votacaoAbre,
        CarbonInterface $votacaoFecha,
    ): self {
        return match (true) {
            $momento->lt($inscricaoAbre) => self::AguardandoInscricoes,
            $momento->lt($inscricaoFecha) => self::InscricoesAbertas,
            $momento->lt($votacaoAbre) => self::EmHomologacao,
            $momento->lt($votacaoFecha) => self::VotacaoAberta,
            default => self::Encerrada,
        };
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::AguardandoInscricoes => 'Aguardando inscrições',
            self::InscricoesAbertas => 'Inscrições abertas',
            self::EmHomologacao => 'Em homologação',
            self::VotacaoAberta => 'Votação aberta',
            self::Encerrada => 'Encerrada',
        };
    }

    /** Tipo do componente <x-selo>. */
    public function selo(): string
    {
        return match ($this) {
            self::AguardandoInscricoes, self::Encerrada => 'neutro',
            self::InscricoesAbertas => 'info',
            self::EmHomologacao => 'aviso',
            self::VotacaoAberta => 'sucesso',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::AguardandoInscricoes => 'hourglass-split',
            self::InscricoesAbertas => 'pencil-square',
            self::EmHomologacao => 'clipboard-check',
            self::VotacaoAberta => 'check2-square',
            self::Encerrada => 'lock',
        };
    }
}
