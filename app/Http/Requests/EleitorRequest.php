<?php

namespace App\Http\Requests;

use App\Support\EleitorRegras;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Cadastrar e editar um eleitor (H4). As regras são as mesmas do CSV (EleitorRegras). */
class EleitorRequest extends FormRequest
{
    /** Espaços nas pontas e repetidos saem antes de validar, como no CSV. */
    protected function prepareForValidation(): void
    {
        $this->merge(collect($this->only(['matricula', 'nome', 'turma', 'email']))
            ->map(fn ($valor) => is_string($valor) ? (Str::squish($valor) ?: null) : $valor)
            ->all());
    }

    public function rules(): array
    {
        $regras = EleitorRegras::campos();
        $regras['matricula'][] = Rule::unique('eleitores', 'matricula')->ignore($this->route('eleitor'));

        return $regras;
    }

    public function attributes(): array
    {
        return EleitorRegras::nomes();
    }

    public function messages(): array
    {
        return EleitorRegras::mensagens() + ['matricula.unique' => 'Já existe um eleitor com esta matrícula.'];
    }
}
