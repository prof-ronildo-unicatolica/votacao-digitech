<?php

namespace App\Support;

/**
 * Regras de um eleitor, as mesmas no formulário (EleitorRequest) e no CSV (ImportadorEleitores).
 * A unicidade da matrícula fica de fora: no formulário ela é validada; no CSV, repetir atualiza.
 */
class EleitorRegras
{
    /** Formato da matrícula: letras, números, ponto e hífen (até 20). */
    public const MATRICULA = '/^[A-Za-z0-9.\-]+$/';

    public static function campos(): array
    {
        return [
            'matricula' => ['required', 'string', 'max:20', 'regex:'.self::MATRICULA],
            'nome' => ['required', 'string', 'max:150'],
            'turma' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    public static function mensagens(): array
    {
        return ['matricula.regex' => 'A matrícula só pode ter letras, números, ponto e hífen.'];
    }

    public static function nomes(): array
    {
        return ['matricula' => 'matrícula', 'nome' => 'nome', 'turma' => 'turma', 'email' => 'e-mail'];
    }
}
