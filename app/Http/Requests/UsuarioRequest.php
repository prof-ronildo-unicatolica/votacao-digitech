<?php

namespace App\Http\Requests;

use App\Enums\Perfil;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/** Criar e editar admin/mesário (H1). Na edição, senha em branco mantém a atual. */
class UsuarioRequest extends FormRequest
{
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'perfil' => ['required', Rule::enum(Perfil::class)],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nome', 'email' => 'e-mail', 'perfil' => 'perfil', 'password' => 'senha'];
    }

    /** O admin não pode tirar o próprio perfil de admin (ficaria sem acesso). */
    public function after(): array
    {
        return [function (Validator $validator) {
            $usuario = $this->route('usuario');

            if ($usuario instanceof User && $usuario->is($this->user()) && $this->input('perfil') !== Perfil::Admin->value) {
                $validator->errors()->add('perfil', 'Você não pode tirar o seu próprio perfil de admin.');
            }
        }];
    }

    /** Dados prontos para gravar (sem a senha quando ela veio em branco). */
    public function dados(): array
    {
        $dados = $this->safe()->only(['name', 'email', 'perfil']);

        if ($this->filled('password')) {
            $dados['password'] = $this->input('password');
        }

        return $dados;
    }
}
