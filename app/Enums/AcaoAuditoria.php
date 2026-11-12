<?php

namespace App\Enums;

/**
 * Catálogo fechado de ações auditadas (D22, docs/AUDITORIA.md, seção 3). Ação nova = um caso
 * novo aqui, um teste e uma linha no catálogo do documento. Fase 1: acesso, terminais,
 * sessões e chapas.
 * Fase 2: cadastros e importações. H10: consulta do resultado e exportação da ata.
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

    // Cadastros (fase 2): o que a comissão configurou antes da votação.
    case EleicaoCriada = 'eleicao.criada';
    case EleicaoAlterada = 'eleicao.alterada';
    case EleicaoExcluida = 'eleicao.excluida';
    case EleicaoAtivada = 'eleicao.ativada';
    case EleicaoDesativada = 'eleicao.desativada';
    case CargoCriado = 'cargo.criado';
    case CargoRenomeado = 'cargo.renomeado';
    case CargoMovido = 'cargo.movido';
    case CargoRemovido = 'cargo.removido';
    case CargosCopiados = 'cargo.copiados';
    case EleitorCriado = 'eleitor.criado';
    case EleitorAlterado = 'eleitor.alterado';
    case EleitorExcluido = 'eleitor.excluido';
    case TerminalCriado = 'terminal.criado';
    case TerminalAlterado = 'terminal.alterado';
    case TerminalExcluido = 'terminal.excluido';
    case UsuarioCriado = 'usuario.criado';
    case UsuarioAlterado = 'usuario.alterado';
    case UsuarioDesativado = 'usuario.desativado';
    case UsuarioReativado = 'usuario.reativado';
    case UsuarioExcluido = 'usuario.excluido';

    // Importações (fase 2), com o nome e o SHA-256 do arquivo, nunca o conteúdo.
    case EleitoresImportados = 'importacao.eleitores';
    case ChapasImportadas = 'importacao.chapas';
    case ImportacaoCancelada = 'importacao.cancelada';

    // Resultado (H10). Só depois do fim da votação: antes dele não há apuração para consultar.
    case ResultadoConsultado = 'resultado.consultado';
    case AtaExportada = 'ata.exportada';

    /** acesso, terminal, sessao, chapa, eleicao, cargo, eleitor, usuario, importacao, resultado ou ata: o prefixo da ação. */
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
            self::EleicaoCriada => 'Eleição cadastrada',
            self::EleicaoAlterada => 'Eleição alterada',
            self::EleicaoExcluida => 'Eleição excluída',
            self::EleicaoAtivada => 'Eleição ativada',
            self::EleicaoDesativada => 'Eleição desativada',
            self::CargoCriado => 'Cargo adicionado',
            self::CargoRenomeado => 'Cargo renomeado',
            self::CargoMovido => 'Cargo mudou de posição',
            self::CargoRemovido => 'Cargo removido',
            self::CargosCopiados => 'Cargos copiados de outra eleição',
            self::EleitorCriado => 'Eleitor cadastrado',
            self::EleitorAlterado => 'Eleitor alterado',
            self::EleitorExcluido => 'Eleitor excluído',
            self::TerminalCriado => 'Terminal cadastrado',
            self::TerminalAlterado => 'Terminal alterado',
            self::TerminalExcluido => 'Terminal excluído',
            self::UsuarioCriado => 'Usuário cadastrado',
            self::UsuarioAlterado => 'Usuário alterado',
            self::UsuarioDesativado => 'Usuário desativado',
            self::UsuarioReativado => 'Usuário reativado',
            self::UsuarioExcluido => 'Usuário excluído',
            self::EleitoresImportados => 'Eleitores importados por CSV',
            self::ChapasImportadas => 'Chapas importadas por CSV',
            self::ImportacaoCancelada => 'Importação cancelada',
            self::ResultadoConsultado => 'Resultado consultado',
            self::AtaExportada => 'Ata exportada',
        };
    }
}
