<?php

namespace App\Support;

use App\Enums\FaseEleicao;
use App\Enums\SituacaoChapa;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Análise das chapas pela comissão eleitoral (H3, D20).
 *
 * Situação: pendente → homologada | indeferida (com motivo); homologada ou indeferida → pendente
 * (reabrir). Tudo isso só até a votação abrir: depois, a urna já mostra as chapas e nada muda.
 *
 * Composição: o admin corrige um candidato só enquanto as inscrições estão abertas (D14), com as
 * mesmas regras da H14 (InscricaoDeChapa::conferir). Corrigir uma chapa homologada a devolve para
 * pendente: a comissão homologou outra composição.
 *
 * Cada operação trava a linha da eleição, como a inscrição e a tela de cargos (D13), e confere a
 * fase dentro da transação.
 */
class HomologacaoDeChapa
{
    public const MOTIVO_MIN = 10;

    public const MOTIVO_MAX = 500;

    public function __construct(private readonly InscricaoDeChapa $inscricao) {}

    public function homologar(Chapa $chapa, User $admin): void
    {
        $this->analisando($chapa, function (Chapa $chapa) use ($admin) {
            if ($chapa->situacao !== SituacaoChapa::Pendente) {
                throw new InscricaoRecusada(['geral' => "A chapa {$chapa->numero} já está {$this->rotulo($chapa)}. Reabra a análise antes de mudar a decisão."]);
            }

            $this->decidir($chapa, $admin, SituacaoChapa::Homologada, null);
        });
    }

    public function indeferir(Chapa $chapa, User $admin, string $motivo): void
    {
        $motivo = Str::squish($motivo);
        if (mb_strlen($motivo) < self::MOTIVO_MIN) {
            throw new InscricaoRecusada(['motivo' => 'Explique o motivo do indeferimento (pelo menos '.self::MOTIVO_MIN.' caracteres): é o registro da decisão da comissão.']);
        }

        $this->analisando($chapa, function (Chapa $chapa) use ($admin, $motivo) {
            if ($chapa->situacao !== SituacaoChapa::Pendente) {
                throw new InscricaoRecusada(['geral' => "A chapa {$chapa->numero} já está {$this->rotulo($chapa)}. Reabra a análise antes de mudar a decisão."]);
            }

            $this->decidir($chapa, $admin, SituacaoChapa::Indeferida, Str::limit($motivo, self::MOTIVO_MAX, ''));
        });
    }

    /** Volta para pendente (homologada ou indeferida), até a votação abrir. */
    public function reabrir(Chapa $chapa, User $admin): void
    {
        $this->analisando($chapa, function (Chapa $chapa) use ($admin) {
            if ($chapa->situacao === SituacaoChapa::Pendente) {
                return;
            }

            $this->decidir($chapa, $admin, SituacaoChapa::Pendente, null);
        });
    }

    /**
     * Troca o candidato de um cargo. Só com as inscrições abertas.
     *
     * @return bool se a chapa estava homologada e voltou para pendente
     *
     * @throws InscricaoRecusada `matricula` (aluno recusado) ou `geral` (fora do período)
     */
    public function trocarCandidato(Chapa $chapa, Cargo $cargo, string $matricula, User $admin): bool
    {
        return DB::transaction(function () use ($chapa, $cargo, $matricula, $admin) {
            try {
                $eleicao = $this->inscricao->travar($chapa->eleicao);
            } catch (InscricaoRecusada $e) {
                throw new InscricaoRecusada(['geral' => 'A composição não pode mais mudar: '.lcfirst($e->erros['geral'])]);
            }

            $chapa = Chapa::whereKey($chapa->getKey())->with('candidatos.eleitor')->firstOrFail();
            $cargos = $eleicao->cargos()->pluck('titulo', 'id')->all();
            $matriculas = $chapa->candidatos->mapWithKeys(fn ($c) => [$c->cargo_id => $c->eleitor->matricula])->all();
            $matriculas[$cargo->id] = preg_replace('/\s+/', '', $matricula);

            [$erros, $alunos] = $this->inscricao->conferir($eleicao, $chapa->nome, $cargos, $matriculas, comissao: true, propria: $chapa);
            if ($erros !== []) {
                throw new InscricaoRecusada(['matricula' => $erros[$cargo->id] ?? implode(' ', $erros)]);
            }

            $chapa->candidatos()->updateOrCreate(
                ['cargo_id' => $cargo->id],
                ['eleicao_id' => $eleicao->id, 'eleitor_id' => $alunos[$cargo->id]->id],
            );

            $voltou = $chapa->situacao === SituacaoChapa::Homologada;
            if ($voltou) {
                $this->decidir($chapa, $admin, SituacaoChapa::Pendente, null);
            }

            return $voltou;
        });
    }

    /** Exclui a chapa (e a composição, em cascata), até a votação abrir. Com votos, o banco recusa. */
    public function excluir(Chapa $chapa): void
    {
        $this->analisando($chapa, function (Chapa $chapa) {
            if ($chapa->votos()->exists()) {
                throw new InscricaoRecusada(['geral' => "A chapa {$chapa->numero} já recebeu votos e faz parte da apuração: não pode ser excluída."]);
            }

            $chapa->delete();
        });
    }

    /** Por que a situação das chapas não pode mudar agora, ou null. */
    public static function bloqueio(Eleicao $eleicao): ?string
    {
        return match ($eleicao->fase()) {
            FaseEleicao::InscricoesAbertas, FaseEleicao::EmHomologacao => null,
            FaseEleicao::AguardandoInscricoes => 'As inscrições ainda não abriram.',
            null => 'As datas desta eleição ainda não foram definidas.',
            default => 'A votação já começou em '.$eleicao->votacao_abre_em->format('d/m/Y \à\s H:i').': as chapas não mudam mais.',
        };
    }

    private function analisando(Chapa $chapa, callable $operacao): void
    {
        DB::transaction(function () use ($chapa, $operacao) {
            $eleicao = Eleicao::whereKey($chapa->eleicao_id)->lockForUpdate()->firstOrFail();
            if ($motivo = self::bloqueio($eleicao)) {
                throw new InscricaoRecusada(['geral' => $motivo]);
            }

            $operacao(Chapa::whereKey($chapa->getKey())->firstOrFail());
        });
    }

    private function decidir(Chapa $chapa, User $admin, SituacaoChapa $situacao, ?string $motivo): void
    {
        $chapa->update([
            'situacao' => $situacao,
            'motivo_indeferimento' => $motivo,
            'analisada_em' => now(),
            'analisada_por_id' => $admin->id,
        ]);
    }

    private function rotulo(Chapa $chapa): string
    {
        return Str::lower($chapa->situacao->rotulo());
    }
}
