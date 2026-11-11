<?php

namespace App\Support;

use App\Enums\FaseEleicao;
use App\Enums\OrigemChapa;
use App\Models\Candidato;
use App\Models\Cargo;
use App\Models\Chapa;
use App\Models\Eleicao;
use App\Models\Eleitor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
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
        $eleicao = Eleicao::whereKey($eleicao->getKey())->lockForUpdate()->firstOrFail();

        // O período é conferido AQUI, no envio, e não só quando a página abriu.
        if ($eleicao->fase() !== FaseEleicao::InscricoesAbertas) {
            throw new InscricaoRecusada(['geral' => self::mensagemForaDoPeriodo($eleicao)]);
        }

        $cargos = $eleicao->cargos()->get();
        if ($cargos->isEmpty()) {
            throw new InscricaoRecusada(['geral' => 'A comissão ainda não definiu os cargos desta eleição. A inscrição não pode ser feita.']);
        }

        [$erros, $alunosPorCargo] = $this->validar($eleicao, $nome, $cargos, $matriculas);
        if ($erros !== []) {
            throw new InscricaoRecusada($erros);
        }

        $chapa = $eleicao->chapas()->create([
            'numero' => ($eleicao->chapas()->max('numero') ?? 0) + 1,
            'nome' => $nome,
            'origem' => $origem,
            'contato_email' => $contatoEmail,
            'contato_telefone' => $contatoTelefone,
        ]);

        foreach ($cargos as $cargo) {
            $chapa->candidatos()->create([
                'eleicao_id' => $eleicao->id,
                'cargo_id' => $cargo->id,
                'eleitor_id' => $alunosPorCargo[$cargo->id]->id,
            ]);
        }

        return $chapa;
    }

    /**
     * @param  Collection<int, Cargo>  $cargos
     * @return array{0: array<string, string>, 1: array<string, Eleitor>}
     */
    private function validar(Eleicao $eleicao, string $nome, Collection $cargos, array $matriculas): array
    {
        $erros = [];

        if ($nome === '') {
            $erros['nome'] = 'Informe o nome da chapa.';
        } elseif ($eleicao->chapas()->whereRaw('LOWER(nome) = ?', [Str::lower($nome)])->exists()) {
            $erros['nome'] = 'Já existe uma chapa com este nome nesta eleição.';
        }

        $porCargo = $cargos->mapWithKeys(fn (Cargo $cargo) => [$cargo->id => trim((string) ($matriculas[$cargo->id] ?? ''))]);
        $alunos = Eleitor::whereIn('matricula', $porCargo->filter()->unique()->values())->get()->keyBy('matricula');
        $jaCandidatos = Candidato::where('eleicao_id', $eleicao->id)->whereIn('eleitor_id', $alunos->pluck('id'))->pluck('eleitor_id')->flip();

        $alunosPorCargo = [];
        $cargoDaMatricula = [];

        foreach ($cargos as $cargo) {
            $campo = "candidatos.{$cargo->id}";
            $matricula = $porCargo[$cargo->id];
            $aluno = $alunos->get($matricula);

            if ($matricula === '') {
                $erros[$campo] = "Informe a matrícula do candidato a {$cargo->titulo}.";
            } elseif (! $aluno) {
                $erros[$campo] = "Matrícula {$matricula} não encontrada. Confira com a comissão se o aluno está cadastrado.";
            } elseif (isset($cargoDaMatricula[$matricula])) {
                $erros[$campo] = "{$aluno->nome} já está no cargo {$cargoDaMatricula[$matricula]} desta chapa: cada aluno ocupa um cargo só.";
            } elseif ($jaCandidatos->has($aluno->id)) {
                $erros[$campo] = "{$aluno->nome} já está em outra chapa desta eleição.";
            } else {
                $alunosPorCargo[$cargo->id] = $aluno;
            }

            if ($matricula !== '' && ! isset($cargoDaMatricula[$matricula])) {
                $cargoDaMatricula[$matricula] = $cargo->titulo;
            }
        }

        return [$erros, $alunosPorCargo];
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
