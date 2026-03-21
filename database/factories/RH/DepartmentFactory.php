<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $nombres = [
            'Dirección General',
            'Recursos Humanos',
            'Contabilidad y Finanzas',
            'Sistemas de Información',
            'Comunicaciones y Prensa',
            'Jurídica',
            'Planeación y Desarrollo',
            'Bienestar Universitario',
            'Gestión Documental',
            'Internacionalización',
            'Secretaría General',
            'Control Interno',
        ];

        return [
            'institution_id' => Institution::factory(),
            'name' => $this->faker->unique()->randomElement($nombres),
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
