<?php

namespace App\Enums;

/**
 * Catálogo fechado de ações auditadas (D22, docs/AUDITORIA.md, seção 3). Ação nova = um caso
 * novo aqui, um teste e uma linha no catálogo do documento. Fase 1: acesso, terminais,
 * sessões e chapas.
 * H10: consulta do resultado e exportação da ata.
 */
enum AcaoAuditoria: string
{
    // Acesso
    case Login = 'acesso.login';
    case LoginFalhou = 'acesso.login_falhou';
    case AcessoBloqueado = 'acesso.bloqueado';
    case Logout = 'acesso.logout';

    // Terminais
    case TerminalAtivado = 'terminal.ativado';
    case TerminalDesativado = 'terminal.desativado';

    // Sessões (mesa e urna). NUNCA guardam a chapa.
    case SessaoLiberada = 'sessao.liberada';
    case SessaoReliberada = 'sessao.reliberada';
    case SessaoCancelada = 'sessao.cancelada';
    case LiberacaoRecusada = 'sessao.liberacao_recusada';
    case SessaoVotou = 'sessao.votou';

    // Chapas
    case ChapaInscrita = 'chapa.inscrita';
    case ChapaHomologada = 'chapa.homologada';
    case ChapaIndeferida = 'chapa.indeferida';
    case ChapaReaberta = 'chapa.reaberta';
    case CandidatoTrocado = 'chapa.candidato_trocado';
    case ChapaExcluida = 'chapa.excluida';

    // Resultado (H10). Só depois do fim da votação: antes dele não há apuração para consultar.
    case ResultadoConsultado = 'resultado.consultado';
    case AtaExportada = 'ata.exportada';

    /** acesso, terminal, sessao, chapa, resultado ou ata: o prefixo da ação. */
    public function categoria(): string
    {
        return strstr($this->value, '.', true);
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Login => 'Entrou no sistema',
            self::LoginFalhou => 'Tentativa de login recusada',
            self::AcessoBloqueado => 'Login bloqueado por excesso de tentativas',
            self::Logout => 'Saiu do sistema',
            self::TerminalAtivado => 'Terminal ativado',
            self::TerminalDesativado => 'Terminal desativado',
            self::SessaoLiberada => 'Eleitor liberado',
            self::SessaoReliberada => 'Eleitor liberado de novo',
            self::SessaoCancelada => 'Sessão cancelada',
            self::LiberacaoRecusada => 'Liberação recusada',
            self::SessaoVotou => 'Eleitor votou',
            self::ChapaInscrita => 'Chapa inscrita pelo formulário',
            self::ChapaHomologada => 'Chapa homologada',
            self::ChapaIndeferida => 'Chapa indeferida',
            self::ChapaReaberta => 'Análise da chapa reaberta',
            self::CandidatoTrocado => 'Candidato trocado',
            self::ChapaExcluida => 'Chapa excluída',
            self::ResultadoConsultado => 'Resultado consultado',
            self::AtaExportada => 'Ata exportada',
        };
    }
}
