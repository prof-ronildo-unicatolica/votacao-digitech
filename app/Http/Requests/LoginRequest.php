<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login de admin e mesário (H1).
 *
 * Limite: 5 tentativas ERRADAS por minuto para o mesmo e-mail + IP bloqueiam por 1 minuto.
 * Só os erros contam (por isso RateLimiter aqui, e não o middleware `throttle`, que contaria
 * também os acertos). Acertar zera o contador.
 */
class LoginRequest extends FormRequest
{
    public const MAX_TENTATIVAS = 5;

    public const BLOQUEIO_SEGUNDOS = 60;

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'e-mail', 'password' => 'senha'];
    }

    /** @throws ValidationException */
    public function autenticar(): void
    {
        $this->garantirQueNaoEstaBloqueado();

        $credenciais = $this->only('email', 'password');

        if (! Auth::attemptWhen($credenciais, fn (User $usuario) => $usuario->ativo, $this->boolean('remember'))) {
            RateLimiter::hit($this->chaveDoLimite(), self::BLOQUEIO_SEGUNDOS);

            throw ValidationException::withMessages(['email' => $this->motivoDaFalha()]);
        }

        RateLimiter::clear($this->chaveDoLimite());
    }

    /** Senha certa de usuário desativado ganha mensagem própria; o resto, mensagem genérica. */
    private function motivoDaFalha(): string
    {
        $usuario = User::where('email', (string) $this->input('email'))->first();

        if ($usuario && ! $usuario->ativo && Hash::check((string) $this->input('password'), $usuario->password)) {
            return 'Seu usuário foi desativado. Procure a comissão eleitoral.';
        }

        return 'E-mail ou senha incorretos.';
    }

    /** @throws ValidationException */
    private function garantirQueNaoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->chaveDoLimite(), self::MAX_TENTATIVAS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => 'Muitas tentativas erradas. Tente de novo em '.RateLimiter::availableIn($this->chaveDoLimite()).' segundos.',
        ]);
    }

    private function chaveDoLimite(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
