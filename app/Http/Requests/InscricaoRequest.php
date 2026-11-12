<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formato dos campos da inscrição pública (H14). As regras de negócio (período, cargos,
 * matrículas, aluno em outra chapa) ficam em App\Support\InscricaoDeChapa, as mesmas do CSV (H15).
 */
class InscricaoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'contato_email' => ['required', 'string', 'email', 'max:255'],
            'contato_telefone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\-\s]{8,20}$/'],
            'candidatos' => ['required', 'array'],
            'candidatos.*' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'nome da chapa',
            'contato_email' => 'e-mail de contato',
            'contato_telefone' => 'telefone de contato',
            'candidatos.*' => 'matrícula',
        ];
    }

    public function messages(): array
    {
        return [
            'contato_telefone.regex' => 'Informe o telefone só com números, espaços, parênteses ou hífen. Ex.: (88) 99999-0000.',
            'candidatos.required' => 'Informe a matrícula de cada candidato.',
        ];
    }
}
