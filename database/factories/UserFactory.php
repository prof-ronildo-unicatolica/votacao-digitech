<?php

namespace Database\Factories;

use App\Enums\Perfil;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Senha padrão usada pela factory (hash calculado uma vez).
     */
    protected static ?string $password;

    /**
     * Estado padrão do model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'perfil' => Perfil::Mesario,
            'ativo' => true,
        ];
    }

    /** Estado: admin (comissão eleitoral). */
    public function admin(): static
    {
        return $this->state(fn () => ['perfil' => Perfil::Admin]);
    }

    /** Estado: usuário desativado (não entra mais). */
    public function desativado(): static
    {
        return $this->state(fn () => ['ativo' => false]);
    }

    /**
     * Estado: e-mail ainda não verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
