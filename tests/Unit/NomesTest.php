<?php

namespace Tests\Unit;

use App\Support\Nomes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** H15 · Padronização dos nomes de cargos e chapas vindos de planilha. */
class NomesTest extends TestCase
{
    public static function nomes(): array
    {
        return [
            'tudo maiúsculo' => ['PRESIDENTE', 'Presidente'],
            'hífen' => ['VICE-PRESIDENTE', 'Vice-presidente'],
            'tudo minúsculo com conectivo' => ['diretor de esportes', 'Diretor de Esportes'],
            'espaços sobrando' => ['  Movimento    Estudantil ', 'Movimento Estudantil'],
            'ordinal com o' => ['1o secretário', '1º Secretário'],
            'ordinal com grau' => ['2° SECRETÁRIO', '2º Secretário'],
            'ordinal feminino' => ['1a tesoureira', '1ª Tesoureira'],
            'misto é respeitado' => ['DCE Livre', 'DCE Livre'],
            'sigla curta em maiúsculas' => ['CHAPA UNE DE LUTA', 'Chapa UNE de Luta'],
            'acentos' => ['INOVAÇÃO ACADÊMICA', 'Inovação Acadêmica'],
            'primeira letra do misto' => ['chapa Nova', 'Chapa Nova'],
            'número no meio não vira ordinal' => ['Chapa 2024', 'Chapa 2024'],
        ];
    }

    #[DataProvider('nomes')]
    public function test_formatar(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, Nomes::formatar($entrada));
    }

    public function test_chave_ignora_acento_maiuscula_espaco_e_pontuacao(): void
    {
        $this->assertSame(Nomes::chave('Vice-presidente'), Nomes::chave('VICE PRESIDENTE'));
        $this->assertSame(Nomes::chave('1º Secretário'), Nomes::chave('1o secretario'));
        $this->assertSame(Nomes::chave('Número da chapa'), Nomes::chave('numero_da_chapa'));
        $this->assertNotSame(Nomes::chave('1º Secretário'), Nomes::chave('2º Secretário'));
    }

    public function test_lista(): void
    {
        $this->assertSame('A', Nomes::lista(['A']));
        $this->assertSame('A e B', Nomes::lista(['A', 'B']));
        $this->assertSame('A, B e C', Nomes::lista(['A', 'B', 'C']));
    }
}
