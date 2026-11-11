<?php

namespace Tests\Unit;

use App\Enums\FaseEleicao;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** H2 · Fase da eleição calculada pelas quatro datas (função pura, sem banco). */
class FaseEleicaoTest extends TestCase
{
    public static function momentos(): array
    {
        return [
            'antes das inscrições' => ['2026-11-09 23:59', FaseEleicao::AguardandoInscricoes],
            'abertura das inscrições (inclui)' => ['2026-11-10 08:00', FaseEleicao::InscricoesAbertas],
            'durante as inscrições' => ['2026-11-15 12:00', FaseEleicao::InscricoesAbertas],
            'fim das inscrições (exclui)' => ['2026-11-20 18:00', FaseEleicao::EmHomologacao],
            'entre inscrição e votação' => ['2026-11-22 10:00', FaseEleicao::EmHomologacao],
            'início da votação (inclui)' => ['2026-11-25 08:00', FaseEleicao::VotacaoAberta],
            'durante a votação' => ['2026-11-25 17:59', FaseEleicao::VotacaoAberta],
            'fim da votação (exclui)' => ['2026-11-25 18:00', FaseEleicao::Encerrada],
            'muito depois' => ['2027-01-01 00:00', FaseEleicao::Encerrada],
        ];
    }

    #[DataProvider('momentos')]
    public function test_fase_em_cada_momento(string $momento, FaseEleicao $esperada): void
    {
        $fase = FaseEleicao::em(
            Carbon::parse($momento),
            Carbon::parse('2026-11-10 08:00'),
            Carbon::parse('2026-11-20 18:00'),
            Carbon::parse('2026-11-25 08:00'),
            Carbon::parse('2026-11-25 18:00'),
        );

        $this->assertSame($esperada, $fase);
    }

    public function test_sem_intervalo_de_homologacao_vai_direto_para_a_votacao(): void
    {
        $fecha = Carbon::parse('2026-11-20 18:00');

        $fase = FaseEleicao::em($fecha, Carbon::parse('2026-11-10 08:00'), $fecha, $fecha, Carbon::parse('2026-11-21 18:00'));

        $this->assertSame(FaseEleicao::VotacaoAberta, $fase);
    }
}
