<?php

namespace App\Http\Requests;

use App\Enums\Perfil;
use App\Models\Candidato;
use App\Models\Eleicao;
use App\Models\User;
use App\Support\EleitorRegras;
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
            'matricula' => ['nullable', 'string', 'max:20', 'regex:'.EleitorRegras::MATRICULA, Rule::exists('eleitores', 'matricula'), Rule::unique('users', 'matricula')->ignore($usuario)],
            'perfil' => ['required', Rule::enum(Perfil::class)],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nome', 'email' => 'e-mail', 'matricula' => 'matrícula', 'perfil' => 'perfil', 'password' => 'senha'];
    }

    public function messages(): array
    {
        return [
            'matricula.regex' => EleitorRegras::mensagens()['matricula.regex'],
            'matricula.exists' => 'Esta matrícula não está no eleitorado. Deixe em branco se a pessoa não for aluno.',
            'matricula.unique' => 'Esta matrícula já está em outro usuário.',
        ];
    }

    /** O admin não pode tirar o próprio perfil de admin (ficaria sem acesso). */
    public function after(): array
    {
        return [function (Validator $validator) {
            $usuario = $this->route('usuario');

            if ($usuario instanceof User && $usuario->is($this->user()) && $this->input('perfil') !== Perfil::Admin->value) {
                $validator->errors()->add('perfil', 'Você não pode tirar o seu próprio perfil de admin.');
            }

            // D19: quem trabalha na eleição não concorre. Candidato da eleição atual não vira usuário.
            $eleicao = Eleicao::atual();
            if ($eleicao && $this->filled('matricula') && ! $validator->errors()->has('matricula')) {
                $candidatura = Candidato::where('eleicao_id', $eleicao->id)
                    ->whereHas('eleitor', fn ($q) => $q->where('matricula', $this->input('matricula')))
                    ->with('chapa')->first();
                if ($candidatura) {
                    $validator->errors()->add('matricula', "Este aluno é candidato na chapa {$candidatura->chapa->numero} da eleição atual: candidato não pode ser mesário nem admin.");
                }
            }
        }];
    }

    /** Dados prontos para gravar (sem a senha quando ela veio em branco). */
    public function dados(): array
    {
        $dados = $this->safe()->only(['name', 'email', 'perfil']);
        $dados['matricula'] = $this->filled('matricula') ? trim($this->input('matricula')) : null;

        if ($this->filled('password')) {
            $dados['password'] = $this->input('password');
        }

        return $dados;
    }
}
