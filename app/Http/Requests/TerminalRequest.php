<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Cadastrar e editar terminal (H5). O pareamento não passa por aqui: é em /terminal/ativar. */
class TerminalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'numero' => ['required', 'integer', 'min:1', 'max:999', Rule::unique('terminais', 'numero')->ignore($this->route('terminal'))],
            'local' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['numero' => 'número', 'local' => 'local'];
    }

    public function messages(): array
    {
        return ['numero.unique' => 'Já existe um terminal com este número.'];
    }
}
