<?php

namespace App\Support;

use App\Enums\AcaoAuditoria;
use App\Enums\SituacaoSessao;
use App\Enums\TipoVoto;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\SessaoVotacao;
use App\Models\Terminal;
use App\Models\Voto;
use Illuminate\Support\Facades\DB;

/**
 * Registra um voto (H8, D24). A ordem é a de MODELO_DADOS.md, "Registro de um voto":
 * REIVINDICAR a sessão primeiro (UPDATE ... WHERE situacao = 'aberta', atômico) e só então
 * gravar o voto, na mesma transação. Duplo clique, F5 ou duas abas: só uma requisição muda a
 * sessão; as outras encontram a sessão já "votou".
 *
 * - O terminal vem do cookie (middleware `terminal`), nunca do formulário (D16).
 * - A eleição do voto vem da SESSÃO, nunca do formulário.
 * - `versao` é a da liberação que estava na tela (D23): se o mesário cancelou e liberou outra
 *   pessoa neste terminal, a confirmação antiga não vale para a nova.
 * - A escolha precisa estar na cédula: chapa homologada da eleição (chapasNaUrna, D20), branco ou nulo.
 * - Sigilo (D3): o voto não tem eleitor, terminal nem horário; a auditoria registra só que a
 *   sessão votou (D22); nada da escolha vai para log, sessão HTTP ou flash.
 */
class RegistroDeVoto
{
    public const JA_REGISTRADO = 'ja_registrado';

    public const REGISTRADO = 'registrado';

    /**
     * @return string REGISTRADO, ou JA_REGISTRADO quando esta liberação já tinha votado (duplo envio)
     *
     * @throws VotoRecusado
     */
    public function votar(Terminal $terminal, string $versao, string $escolha): string
    {
        return DB::transaction(function () use ($terminal, $versao, $escolha) {
            $sessao = $this->sessaoDaTela($terminal, $versao);

            if ($sessao->situacao === SituacaoSessao::Votou) {
                return self::JA_REGISTRADO;
            }
            if (! $sessao->emAndamento()) {
                throw new VotoRecusado('O tempo desta liberação acabou ou ela foi cancelada. Seu voto não foi registrado. Chame o mesário.');
            }
            if ($motivo = LiberacaoDeEleitor::bloqueio($sessao->eleicao)) {
                throw new VotoRecusado($motivo.' Seu voto não foi registrado.');
            }

            [$tipo, $chapa] = $this->opcao($sessao->eleicao, $escolha);

            // 1. Reivindica a sessão: só UMA requisição consegue mudar "aberta" para "votou".
            $afetadas = SessaoVotacao::whereKey($sessao->id)
                ->where('terminal_id', $terminal->id)
                ->where('situacao', SituacaoSessao::Aberta)
                ->where('expira_em', '>', now())
                ->update(['situacao' => SituacaoSessao::Votou, 'votou_em' => now()]);

            if ($afetadas !== 1) {
                // Outra requisição desta mesma liberação acabou de votar (duplo clique).
                if ($sessao->fresh()->situacao === SituacaoSessao::Votou) {
                    return self::JA_REGISTRADO;
                }
                throw new VotoRecusado('Esta liberação não está mais aberta. Seu voto não foi registrado. Chame o mesário.');
            }

            // 2. O voto: eleição da SESSÃO; sem eleitor, terminal ou horário.
            Voto::create(['eleicao_id' => $sessao->eleicao_id, 'chapa_id' => $chapa?->id, 'tipo' => $tipo]);

            // 3. Auditoria: a sessão votou. Nunca a escolha (Auditoria recusa as chaves de chapa).
            Auditoria::registrar(AcaoAuditoria::SessaoVotou, $sessao, ['terminal' => $terminal->numero]);

            return self::REGISTRADO;
        });
    }

    /**
     * A opção escolhida, conferida contra a cédula, para a tela de confirmação.
     *
     * @return array{0: TipoVoto, 1: Chapa|null}
     *
     * @throws VotoRecusado
     */
    public function opcao(Eleicao $eleicao, string $escolha): array
    {
        return match ($escolha) {
            TipoVoto::Branco->value => [TipoVoto::Branco, null],
            TipoVoto::Nulo->value => [TipoVoto::Nulo, null],
            default => [TipoVoto::Chapa, $eleicao->chapasNaUrna()->whereKey($escolha)->with('composicao')->first()
                ?? throw new VotoRecusado('Esta opção não está na cédula. Escolha de novo.')],
        };
    }

    /**
     * A sessão deste terminal cuja liberação estava na tela (mesma versão), em qualquer situação.
     *
     * @throws VotoRecusado
     */
    public function sessaoDaTela(Terminal $terminal, string $versao): SessaoVotacao
    {
        $eleicao = Eleicao::atual();
        $sessao = $eleicao
            ? $terminal->sessoes()->where('eleicao_id', $eleicao->id)->with('eleicao')->get()->first(fn (SessaoVotacao $s) => hash_equals($s->versao(), $versao))
            : null;

        return $sessao ?? throw new VotoRecusado('Esta liberação não vale mais para este terminal (o mesário pode ter cancelado ou liberado outra pessoa). Seu voto não foi registrado. Chame o mesário.');
    }
}
