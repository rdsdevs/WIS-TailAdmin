<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * La contraseña por defecto usada en los tests.
     */
    protected static ?string $password = null;

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => null,
            'document_type' => 'CC',
            'document_number' => fake()->unique()->numerify('##########'),
            'document_issued_at' => fake()->dateTimeBetween('-30 years', '-1 year')->format('Y-m-d'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Clave123*'),
            'is_active' => true,
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Estado: usuario inactivo.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Estado: email no verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
