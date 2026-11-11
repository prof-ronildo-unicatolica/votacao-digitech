<?php

namespace App\Support;

use App\Enums\FaseEleicao;
use App\Enums\OrigemChapa;
use App\Enums\Perfil;
use App\Models\Candidato;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Inscreve uma chapa completa (D13, D14). Mesmas regras para o formulário público (H14) e o
 * CSV da comissão (H15): tudo ou nada, a chapa e um candidato por cargo na mesma transação.
 *
 * A transação trava a linha da eleição (lockForUpdate), a mesma que a tela de cargos trava
 * (CargosDaEleicao): a lista de cargos não muda enquanto uma chapa está sendo gravada, e duas
 * inscrições ao mesmo tempo esperam uma pela outra (cada uma recebe o seu número).
 */
class InscricaoDeChapa
{
    /** Se mesmo com a trava o banco recusar por UNIQUE (número, nome ou aluno), tenta de novo. */
    private const TENTATIVAS = 3;

    /**
     * @param  array<string, string|null>  $matriculas  matrícula de cada cargo, indexada pelo id do cargo
     *
     * @throws InscricaoRecusada com uma mensagem por campo
     */
    public function inscrever(
        Eleicao $eleicao,
        string $nome,
        array $matriculas,
        ?string $contatoEmail,
        ?string $contatoTelefone,
        OrigemChapa $origem,
    ): Chapa {
        for ($tentativa = 1; ; $tentativa++) {
            try {
                return DB::transaction(fn () => $this->gravar($eleicao, Str::squish($nome), $matriculas, $contatoEmail, $contatoTelefone, $origem));
            } catch (UniqueConstraintViolationException $e) {
                // Outra inscrição gravou entre a validação e o INSERT. Na próxima volta a
                // validação enxerga o que ela gravou (nome ou aluno repetido) ou pega o próximo número.
                if ($tentativa >= self::TENTATIVAS) {
                    throw new InscricaoRecusada(['geral' => 'Muitas inscrições ao mesmo tempo. Envie de novo em alguns segundos.']);
                }
            }
        }
    }

    private function gravar(Eleicao $eleicao, string $nome, array $matriculas, ?string $contatoEmail, ?string $contatoTelefone, OrigemChapa $origem): Chapa
    {
        $eleicao = $this->travar($eleicao);

        $cargos = $eleicao->cargos()->get();
        if ($cargos->isEmpty()) {
            throw new InscricaoRecusada(['geral' => 'A comissão ainda não definiu os cargos desta eleição. A inscrição não pode ser feita.']);
        }

        [$erros, $alunos] = $this->conferir($eleicao, $nome, $cargos->pluck('titulo', 'id')->all(), $matriculas);
        if ($erros !== []) {
            $porCampo = [];
            foreach ($erros as $chave => $mensagem) {
                $porCampo[$chave === 'nome' ? 'nome' : "candidatos.{$chave}"] = $mensagem;
            }

            throw new InscricaoRecusada($porCampo);
        }

        return $this->criar(
            $eleicao,
            $nome,
            ($eleicao->chapas()->max('numero') ?? 0) + 1,
            array_map(fn (Eleitor $aluno) => $aluno->id, $alunos),
            $contatoEmail,
            $contatoTelefone,
            $origem,
        );
    }

    /**
     * Trava a linha da eleição até o fim da transação e confere o período NESTE momento
     * (no envio, e não só quando a página abriu). Chamar dentro de DB::transaction.
     *
     * @throws InscricaoRecusada fora do período de inscrição
     */
    public function travar(Eleicao $eleicao): Eleicao
    {
        $eleicao = Eleicao::whereKey($eleicao->getKey())->lockForUpdate()->firstOrFail();

        if ($eleicao->fase() !== FaseEleicao::InscricoesAbertas) {
            throw new InscricaoRecusada(['geral' => self::mensagemForaDoPeriodo($eleicao)]);
        }

        return $eleicao;
    }

    /**
     * Regras de uma chapa (D13, D14, D19), sem gravar nada: nome preenchido e livre na eleição;
     * um aluno cadastrado por cargo (matrícula no formato de EleitorRegras); o mesmo aluno em um
     * cargo só; aluno fora de outra chapa; e quem é da equipe (usuário admin ou mesário com
     * essa matrícula) não é candidato. O banco garante as duas últimas de novo: UNIQUE
     * (eleicao_id, eleitor_id) e (chapa_id, cargo_id) em `candidatos`.
     *
     * Os cargos vêm como chave => título: o formulário usa o id do cargo; o CSV (H15) pode
     * conferir cargos que ainda vão ser criados pelo cabeçalho do arquivo.
     *
     * @param  array<string, string>  $cargos  chave => título, na ordem da cédula
     * @param  array<string, string|null>  $matriculas  chave do cargo => matrícula
     * @param  bool  $comissao  mensagens para a comissão (CSV) em vez do representante (formulário)
     * @param  Chapa|null  $propria  a chapa que está sendo corrigida (H3): o nome e os alunos dela não contam como "outra chapa"
     * @return array{0: array<string, string>, 1: array<string, Eleitor>} erros (chave do cargo ou `nome`) e o aluno de cada cargo
     */
    public function conferir(Eleicao $eleicao, string $nome, array $cargos, array $matriculas, bool $comissao = false, ?Chapa $propria = null): array
    {
        $erros = [];

        if ($nome === '') {
            $erros['nome'] = 'Informe o nome da chapa.';
        } elseif ($eleicao->chapas()->whereRaw('LOWER(nome) = ?', [Str::lower($nome)])->when($propria, fn ($q) => $q->whereKeyNot($propria->getKey()))->exists()) {
            $erros['nome'] = 'Já existe uma chapa com este nome nesta eleição.';
        }

        $porCargo = collect($cargos)->map(fn ($titulo, $chave) => trim((string) ($matriculas[$chave] ?? '')));
        $alunos = Eleitor::whereIn('matricula', $porCargo->filter()->unique()->values())->get()->keyBy('matricula');
        $daEquipe = User::whereIn('matricula', $porCargo->filter()->unique()->values())->pluck('perfil', 'matricula');
        $jaCandidatos = Candidato::where('eleicao_id', $eleicao->id)->whereIn('eleitor_id', $alunos->pluck('id'))
            ->when($propria, fn ($q) => $q->where('chapa_id', '!=', $propria->getKey()))
            ->pluck('eleitor_id')->flip();

        $alunosPorCargo = [];
        $cargoDaMatricula = [];

        foreach ($cargos as $chave => $titulo) {
            $matricula = $porCargo[$chave];
            $aluno = $alunos->get($matricula);

            if ($matricula === '') {
                $erros[$chave] = "Informe a matrícula do candidato a {$titulo}.";
            } elseif (mb_strlen($matricula) > 20 || ! preg_match(EleitorRegras::MATRICULA, $matricula)) {
                $erros[$chave] = "Matrícula “{$matricula}” em formato inválido: use só letras, números, ponto e hífen.";
            } elseif (! $aluno) {
                $erros[$chave] = $comissao
                    ? "Matrícula {$matricula} não cadastrada no eleitorado."
                    : "Matrícula {$matricula} não encontrada. Confira com a comissão se o aluno está cadastrado.";
            } elseif (isset($cargoDaMatricula[$matricula])) {
                $erros[$chave] = "{$aluno->nome} já está no cargo {$cargoDaMatricula[$matricula]} desta chapa: cada aluno ocupa um cargo só.";
            } elseif ($daEquipe->has($matricula)) {
                $erros[$chave] = $daEquipe[$matricula] === Perfil::Admin
                    ? "{$aluno->nome} faz parte da comissão eleitoral (admin) e não pode ser candidato."
                    : "{$aluno->nome} é mesário e não pode ser candidato: quem libera votos não concorre.";
            } elseif ($jaCandidatos->has($aluno->id)) {
                $erros[$chave] = "{$aluno->nome} já está em outra chapa desta eleição.";
            } else {
                $alunosPorCargo[$chave] = $aluno;
            }

            if ($matricula !== '' && ! isset($cargoDaMatricula[$matricula])) {
                $cargoDaMatricula[$matricula] = $titulo;
            }
        }

        return [$erros, $alunosPorCargo];
    }

    /**
     * Grava a chapa (pendente) e um candidato por cargo. Só depois de travar() e conferir().
     *
     * @param  array<string, string>  $eleitorPorCargo  id do cargo => id do eleitor
     */
    public function criar(
        Eleicao $eleicao,
        string $nome,
        int $numero,
        array $eleitorPorCargo,
        ?string $contatoEmail,
        ?string $contatoTelefone,
        OrigemChapa $origem,
    ): Chapa {
        $chapa = $eleicao->chapas()->create([
            'numero' => $numero,
            'nome' => $nome,
            'origem' => $origem,
            'contato_email' => $contatoEmail,
            'contato_telefone' => $contatoTelefone,
        ]);

        foreach ($eleitorPorCargo as $cargoId => $eleitorId) {
            $chapa->candidatos()->create([
                'eleicao_id' => $eleicao->id,
                'cargo_id' => $cargoId,
                'eleitor_id' => $eleitorId,
            ]);
        }

        return $chapa;
    }

    /** Por que não dá para inscrever agora, com as datas do período. */
    public static function mensagemForaDoPeriodo(Eleicao $eleicao): string
    {
        $formato = 'd/m/Y \à\s H:i';

        return match ($eleicao->fase()) {
            FaseEleicao::AguardandoInscricoes => 'As inscrições ainda não abriram. Período: de '.$eleicao->inscricao_abre_em->format($formato).' até '.$eleicao->inscricao_fecha_em->format($formato).'.',
            null => 'As datas desta eleição ainda não foram definidas pela comissão.',
            default => 'As inscrições foram encerradas em '.$eleicao->inscricao_fecha_em->format($formato).'.',
        };
    }
}
