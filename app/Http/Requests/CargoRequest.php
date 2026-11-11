<?php

namespace App\Http\Requests;

use App\Models\Cargo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * Título de um cargo (H13): obrigatório e único na eleição, sem diferenciar maiúsculas
 * ("Presidente" e "presidente" são o mesmo cargo). O banco também tem UNIQUE (eleicao_id, titulo).
 */
class CargoRequest extends FormRequest
{
    /** O formulário de cada linha da lista usa um nome próprio, para o erro aparecer na linha certa. */
    public function campo(): string
    {
        $cargo = $this->route('cargo');

        return $cargo ? "titulo_{$cargo->id}" : 'titulo';
    }

    protected function prepareForValidation(): void
    {
        $valor = $this->input($this->campo());
        $this->merge([$this->campo() => is_string($valor) ? Str::squish($valor) : $valor]);
    }

    public function rules(): array
    {
        return [$this->campo() => ['required', 'string', 'max:80']];
    }

    public function attributes(): array
    {
        return [$this->campo() => 'título do cargo'];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $repetido = Cargo::where('eleicao_id', $this->route('eleicao')->id)
                ->whereRaw('LOWER(titulo) = ?', [Str::lower($this->titulo())])
                ->when($this->route('cargo'), fn ($q, Cargo $cargo) => $q->whereKeyNot($cargo->id))
                ->exists();

            if ($repetido) {
                $validator->errors()->add($this->campo(), 'Esta eleição já tem um cargo com este título.');
            }
        }];
    }

    public function titulo(): string
    {
        return (string) $this->input($this->campo());
    }
}
