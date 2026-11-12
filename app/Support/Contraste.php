<?php

namespace App\Support;

/** Contraste entre duas cores pela fórmula da WCAG 2.x (1:1 a 21:1). AA para texto: ≥ 4.5. */
final class Contraste
{
    public static function razao(string $hexA, string $hexB): float
    {
        [$claro, $escuro] = [
            max(self::luminancia($hexA), self::luminancia($hexB)),
            min(self::luminancia($hexA), self::luminancia($hexB)),
        ];

        return ($claro + 0.05) / ($escuro + 0.05);
    }

    private static function luminancia(string $hex): float
    {
        [$r, $g, $b] = array_map(
            fn (string $canal) => ($v = hexdec($canal) / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
            str_split(substr(ltrim($hex, '#'), 0, 6), 2),
        );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
