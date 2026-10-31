<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../design/tokens/gerar-css.php';

/**
 * A identidade visual tem uma fonte única: design/tokens/tokens.json.
 * Estes testes garantem que o CSS não foi editado à mão e que as combinações
 * de texto principais continuam acessíveis (WCAG AA, contraste ≥ 4.5:1).
 */
class TokensTest extends TestCase
{
    private const JSON = __DIR__.'/../../design/tokens/tokens.json';

    public function test_tokens_css_esta_sincronizado_com_o_json(): void
    {
        $this->assertSame(
            gerarCss(self::JSON),
            file_get_contents(__DIR__.'/../../public/css/tokens.css'),
            'public/css/tokens.css desatualizado: rode php design/tokens/gerar-css.php',
        );
    }

    /** @return array<string, array{string, string}> */
    public static function combinacoesDeTexto(): array
    {
        return [
            'texto principal na página' => ['grafite.900', 'grafite.50'],
            'texto secundário no cartão' => ['grafite.600', 'grafite.0'],
            'texto secundário na página' => ['grafite.600', 'grafite.50'],
            'botão primário' => ['grafite.0', 'laranja.700'],
            'botão primário em hover' => ['grafite.0', 'laranja.800'],
            'link no cartão' => ['laranja.700', 'grafite.0'],
            'texto marca no fundo de destaque' => ['laranja.700', 'laranja.50'],
            'texto inverso na barra escura' => ['grafite.300', 'grafite.900'],
            'laranja da marca na barra escura' => ['laranja.500', 'grafite.900'],
        ];
    }

    #[DataProvider('combinacoesDeTexto')]
    public function test_contraste_de_texto_atende_wcag_aa(string $texto, string $fundo): void
    {
        $cores = json_decode(file_get_contents(self::JSON), true)['cor'];
        $hex = fn (string $c) => $cores[explode('.', $c)[0]][explode('.', $c)[1]]['$value'];

        $contraste = self::contraste($hex($texto), $hex($fundo));

        $this->assertGreaterThanOrEqual(4.5, $contraste, sprintf('%s sobre %s: %.2f:1', $texto, $fundo, $contraste));
    }

    public function test_cores_semanticas_atendem_wcag_aa_no_proprio_fundo(): void
    {
        $cores = json_decode(file_get_contents(self::JSON), true)['cor'];

        foreach (['sucesso', 'aviso', 'perigo', 'info'] as $papel) {
            $contraste = self::contraste($cores[$papel]['texto']['$value'], $cores[$papel]['fundo']['$value']);
            $this->assertGreaterThanOrEqual(4.5, $contraste, "$papel: {$contraste}:1");
        }
    }

    private static function contraste(string $a, string $b): float
    {
        $luminancia = function (string $hex): float {
            [$r, $g, $b] = array_map(
                fn (string $c) => ($v = hexdec($c) / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
                str_split(ltrim($hex, '#'), 2),
            );

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        [$claro, $escuro] = [max($luminancia($a), $luminancia($b)), min($luminancia($a), $luminancia($b))];

        return ($claro + 0.05) / ($escuro + 0.05);
    }
}
