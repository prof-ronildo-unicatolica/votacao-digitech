<?php

/**
 * Gera public/css/tokens.css a partir de design/tokens/tokens.json.
 *
 *   php design/tokens/gerar-css.php
 *
 * Cada token vira uma variável CSS com prefixo --dt-: cor.laranja.500 → --dt-cor-laranja-500.
 * Aliases ({cor.grafite.900}) viram var(--dt-cor-grafite-900), então mudar a escala
 * muda o tema junto. Não edite tokens.css à mão: o teste TokensTest falha se ele
 * estiver diferente do que este script gera.
 */

const PREFIXO = '--dt-';

function nomeVariavel(array $caminho): string
{
    return PREFIXO.implode('-', $caminho);
}

function valorCss(mixed $valor, ?string $tipo, ?string $reserva = null): string
{
    if (is_string($valor) && preg_match('/^\{([^}]+)\}$/', $valor, $m)) {
        return 'var('.nomeVariavel(explode('.', $m[1])).')';
    }

    if ($tipo === 'fontFamily') {
        $familia = str_contains($valor, ' ') ? "\"$valor\"" : $valor;

        return $reserva ? "$familia, $reserva" : $familia;
    }

    if ($tipo === 'shadow' && is_array($valor)) {
        return implode(' ', [$valor['offsetX'], $valor['offsetY'], $valor['blur'], $valor['spread'], $valor['color']]);
    }

    return (string) $valor;
}

/** @return list<array{string, string}> pares [nome da variável, valor] */
function achatar(array $grupo, array $caminho = [], ?string $tipoHerdado = null): array
{
    $tipo = $grupo['$type'] ?? $tipoHerdado;

    if (array_key_exists('$value', $grupo)) {
        // Fontes: o Figma recebe só o nome da família; o CSS ganha a pilha de reserva.
        $reserva = $grupo['$extensions']['br.digitech.fallback'] ?? null;

        return [[nomeVariavel($caminho), valorCss($grupo['$value'], $tipo, $reserva)]];
    }

    $saida = [];
    foreach ($grupo as $chave => $filho) {
        if (str_starts_with((string) $chave, '$') || ! is_array($filho)) {
            continue;
        }
        array_push($saida, ...achatar($filho, [...$caminho, (string) $chave], $tipo));
    }

    return $saida;
}

function gerarCss(string $arquivoJson): string
{
    $tokens = json_decode(file_get_contents($arquivoJson), true, flags: JSON_THROW_ON_ERROR);

    $linhas = array_map(fn (array $par) => "    {$par[0]}: {$par[1]};", achatar($tokens));

    return "/* GERADO por design/tokens/gerar-css.php a partir de design/tokens/tokens.json. Não edite à mão. */\n"
        .":root {\n".implode("\n", $linhas)."\n}\n";
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $raiz = dirname(__DIR__, 2);
    $destino = $raiz.'/public/css/tokens.css';
    @mkdir(dirname($destino), recursive: true);
    file_put_contents($destino, gerarCss(__DIR__.'/tokens.json'));
    echo "Gerado: public/css/tokens.css\n";
}
