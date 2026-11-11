<?php

namespace App\Console\Commands;

use App\Enums\Perfil;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Cria (ou promove) um admin. É assim que nasce o PRIMEIRO admin em produção, onde o
 * seed não roda (H12): php artisan digitech:admin
 *
 * A senha é sempre perguntada (nunca por opção), para não ficar no histórico do terminal.
 * Se o e-mail já existe, o usuário vira admin, é reativado e recebe a nova senha.
 */
class CriarAdmin extends Command
{
    protected $signature = 'digitech:admin {--email= : E-mail do admin} {--nome= : Nome do admin}';

    protected $description = 'Cria um admin (comissão eleitoral) ou promove um usuário existente';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('E-mail');
        $existente = User::where('email', $email)->first();
        $nome = $this->option('nome') ?: ($existente?->name ?? $this->ask('Nome'));

        $senha = $this->secret('Senha (mínimo 8 caracteres)');
        $confirmacao = $this->secret('Confirme a senha');

        $validacao = Validator::make(
            ['email' => $email, 'name' => $nome, 'password' => $senha, 'password_confirmation' => $confirmacao],
            [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ],
            [],
            ['email' => 'e-mail', 'name' => 'nome', 'password' => 'senha'],
        );

        if ($validacao->fails()) {
            foreach ($validacao->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        $usuario = User::updateOrCreate(
            ['email' => $email],
            ['name' => $nome, 'password' => $senha, 'perfil' => Perfil::Admin, 'ativo' => true],
        );

        $this->info($existente
            ? "{$usuario->email} agora é admin (ativo, com a nova senha)."
            : "Admin {$usuario->email} criado. Entre em ".route('login'));

        return self::SUCCESS;
    }
}
