<?php

namespace App\Support;

use App\Enums\FaseEleicao;
use App\Enums\SituacaoSessao;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * O mesário libera um eleitor num terminal (H6, D21).
 *
 * Uma sessão por eleitor e eleição (UNIQUE): liberar cria a linha; re-liberar (expirada ou
 * cancelada) ATUALIZA a mesma linha; sessão que votou é final.
 *
 * Concorrência: a transação trava a linha do terminal (dois mesários liberando o mesmo terminal
 * esperam um pelo outro) e a sessão do eleitor, se existir (o mesmo eleitor em dois terminais).
 * A primeira liberação de um eleitor em dois terminais ao mesmo tempo esbarra no UNIQUE.
 */
class LiberacaoDeEleitor
{
    /**
     * @throws LiberacaoRecusada com a mensagem para o mesário
     */
    public function liberar(Eleicao $eleicao, string $matricula, Terminal $terminal, User $mesario): SessaoVotacao
    {
        try {
            return DB::transaction(function () use ($eleicao, $matricula, $terminal, $mesario) {
                $eleicao = Eleicao::whereKey($eleicao->getKey())->sharedLock()->firstOrFail();
                if ($motivo = self::bloqueio($eleicao)) {
                    throw new LiberacaoRecusada($motivo);
                }

                $eleitor = $this->eleitor($matricula);
                if ($mesario->matricula !== null && strcasecmp($mesario->matricula, $eleitor->matricula) === 0) {
                    throw new LiberacaoRecusada('Você não pode liberar o seu próprio voto. Peça a outro mesário.');
                }

                $terminal = Terminal::whereKey($terminal->getKey())->lockForUpdate()->firstOrFail();
                if (! $terminal->estaAtivado()) {
                    throw new LiberacaoRecusada("O terminal {$terminal->numero} não está ativado. Ative o computador em /terminal/ativar antes de liberar.");
                }

                $ocupado = $terminal->sessoes()->emAndamento()->with('eleitor')->first();
                if ($ocupado && $ocupado->eleitor_id !== $eleitor->id) {
                    throw new LiberacaoRecusada("O terminal {$terminal->numero} está com a liberação de {$ocupado->eleitor->nome} (expira em {$ocupado->minutosRestantes()} min). Escolha outro terminal ou cancele aquela sessão.");
                }

                $sessao = SessaoVotacao::where('eleicao_id', $eleicao->id)->where('eleitor_id', $eleitor->id)->lockForUpdate()->with('terminal')->first();
                $this->conferirSessao($sessao, $eleitor);

                $dados = [
                    'terminal_id' => $terminal->id,
                    'mesario_id' => $mesario->id,
                    'situacao' => SituacaoSessao::Aberta,
                    'liberada_em' => now(),
                    'expira_em' => now()->addMinutes(SessaoVotacao::VALIDADE_MINUTOS),
                    'votou_em' => null,
                    'cancelada_em' => null,
                ];

                // Re-liberação: a MESMA linha (o UNIQUE não aceitaria uma segunda).
                if ($sessao) {
                    $sessao->update($dados);

                    return $sessao->fresh(['eleitor', 'terminal']);
                }

                return SessaoVotacao::create($dados + ['eleicao_id' => $eleicao->id, 'eleitor_id' => $eleitor->id])->load(['eleitor', 'terminal']);
            });
        } catch (UniqueConstraintViolationException) {
            throw new LiberacaoRecusada('Outro mesário acabou de fazer a liberação desta matrícula. Confira o painel dos terminais.');
        }
    }

    /**
     * Cancela uma sessão que ainda não votou (aberta ou expirada). Atômico: se o eleitor votou
     * no mesmo instante, o cancelamento não acontece.
     */
    public function cancelar(SessaoVotacao $sessao): void
    {
        $afetadas = SessaoVotacao::whereKey($sessao->getKey())
            ->where('situacao', SituacaoSessao::Aberta)
            ->update(['situacao' => SituacaoSessao::Cancelada, 'cancelada_em' => now()]);

        if ($afetadas !== 1) {
            $situacao = $sessao->fresh()->situacao;
            throw new LiberacaoRecusada($situacao === SituacaoSessao::Votou
                ? 'O eleitor já votou: a sessão não pode mais ser cancelada.'
                : 'Esta sessão já estava encerrada.');
        }
    }

    /**
     * O que o mesário vê ao conferir uma matrícula, antes de liberar.
     *
     * @return array{eleitor: Eleitor, sessao: SessaoVotacao|null, situacao: SituacaoSessao|null, pode: bool, motivo: string|null}
     *
     * @throws LiberacaoRecusada matrícula não encontrada
     */
    public function consultar(Eleicao $eleicao, string $matricula, User $mesario): array
    {
        $eleitor = $this->eleitor($matricula);
        $sessao = SessaoVotacao::where('eleicao_id', $eleicao->id)->where('eleitor_id', $eleitor->id)->with('terminal')->first();

        try {
            if ($mesario->matricula !== null && strcasecmp($mesario->matricula, $eleitor->matricula) === 0) {
                throw new LiberacaoRecusada('Você não pode liberar o seu próprio voto. Peça a outro mesário.');
            }
            $this->conferirSessao($sessao, $eleitor);
            $motivo = null;
        } catch (LiberacaoRecusada $e) {
            $motivo = $e->getMessage();
        }

        return ['eleitor' => $eleitor, 'sessao' => $sessao, 'situacao' => $sessao?->situacaoAtual(), 'pode' => $motivo === null, 'motivo' => $motivo];
    }

    /** Por que não dá para liberar ninguém nesta eleição agora, ou null. */
    public static function bloqueio(Eleicao $eleicao): ?string
    {
        $formato = 'd/m/Y \à\s H:i';

        return match (true) {
            $eleicao->fase() === null => 'As datas desta eleição ainda não foram definidas.',
            $eleicao->fase() === FaseEleicao::Encerrada => 'A votação foi encerrada em '.$eleicao->votacao_fecha_em->format($formato).'.',
            $eleicao->fase() !== FaseEleicao::VotacaoAberta => 'A votação ainda não abriu. Começa em '.$eleicao->votacao_abre_em->format($formato).'.',
            $eleicao->chapasNaUrna()->doesntExist() => 'Nenhuma chapa foi homologada: a urna não teria em quem votar. Fale com a comissão eleitoral.',
            default => null,
        };
    }

    private function eleitor(string $matricula): Eleitor
    {
        $matricula = preg_replace('/\s+/', '', $matricula);
        $eleitor = $matricula === '' ? null : Eleitor::where('matricula', $matricula)->first();

        if (! $eleitor) {
            throw new LiberacaoRecusada($matricula === ''
                ? 'Digite a matrícula do eleitor.'
                : "Matrícula {$matricula} não encontrada no eleitorado. Confira o documento do aluno.");
        }

        return $eleitor;
    }

    private function conferirSessao(?SessaoVotacao $sessao, Eleitor $eleitor): void
    {
        $situacao = $sessao?->situacaoAtual();

        if ($situacao === SituacaoSessao::Votou) {
            throw new LiberacaoRecusada("{$eleitor->nome} já votou nesta eleição, às {$sessao->votou_em?->format('H:i')}.");
        }
        if ($situacao === SituacaoSessao::Aberta) {
            throw new LiberacaoRecusada("{$eleitor->nome} tem uma liberação em andamento no terminal {$sessao->terminal->numero} (expira em {$sessao->minutosRestantes()} min). Para mudar de terminal, cancele aquela sessão primeiro.");
        }
    }
}
