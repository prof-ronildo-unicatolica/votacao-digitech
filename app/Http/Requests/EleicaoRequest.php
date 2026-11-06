<?php

namespace App\Http\Requests;

use App\Models\Eleicao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Criar e editar eleição (H2). Ordem obrigatória das datas:
 *   abertura das inscrições < fim das inscrições <= início da votação < fim da votação
 * Com chapa inscrita, o período de inscrição só pode ser PRORROGADO (D14).
 */
class EleicaoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'inscricao_abre_em' => ['required', 'date'],
            'inscricao_fecha_em' => ['required', 'date', 'after:inscricao_abre_em'],
            'votacao_abre_em' => ['required', 'date', 'after_or_equal:inscricao_fecha_em'],
            'votacao_fecha_em' => ['required', 'date', 'after:votacao_abre_em'],
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'inscricao_abre_em' => 'abertura das inscrições',
            'inscricao_fecha_em' => 'fim das inscrições',
            'votacao_abre_em' => 'início da votação',
            'votacao_fecha_em' => 'fim da votação',
        ];
    }

    public function messages(): array
    {
        return [
            'inscricao_fecha_em.after' => 'O fim das inscrições precisa ser depois da abertura das inscrições.',
            'votacao_abre_em.after_or_equal' => 'O início da votação não pode ser antes do fim das inscrições.',
            'votacao_fecha_em.after' => 'O fim da votação precisa ser depois do início da votação.',
        ];
    }

    /** Regra que depende do banco: período de inscrição de eleição com chapas inscritas. */
    public function after(): array
    {
        return [function (Validator $validator) {
            $eleicao = $this->route('eleicao');

            if (! $eleicao instanceof Eleicao || ! $eleicao->temInscricoes() || $validator->errors()->isNotEmpty()) {
                return;
            }

            if (! Carbon::parse($this->input('inscricao_abre_em'))->equalTo($eleicao->inscricao_abre_em)) {
                $validator->errors()->add('inscricao_abre_em', 'Já há chapas inscritas: a abertura das inscrições não pode mudar.');
            }

            if (Carbon::parse($this->input('inscricao_fecha_em'))->lt($eleicao->inscricao_fecha_em)) {
                $validator->errors()->add('inscricao_fecha_em', 'Já há chapas inscritas: o fim das inscrições só pode ser prorrogado, nunca antecipado.');
            }
        }];
    }
}
