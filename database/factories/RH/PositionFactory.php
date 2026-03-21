<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\Department;
use App\Models\RH\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        $cargos = [
            'Director General',
            'Subdirector',
            'Gerente Administrativo',
            'Coordinador de RH',
            'Analista de Nómina',
            'Auxiliar Administrativo',
            'Profesional Especializado',
            'Asistente de Dirección',
            'Técnico de Sistemas',
            'Contador Público',
            'Asesor Jurídico',
            'Comunicador Social',
            'Bibliotecólogo',
            'Secretaria Ejecutiva',
            'Mensajero',
        ];

        return [
            'institution_id' => Institution::factory(),
            'department_id' => Department::factory(),
            'name' => $this->faker->unique()->randomElement($cargos),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
