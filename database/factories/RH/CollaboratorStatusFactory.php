<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\CollaboratorStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollaboratorStatus>
 */
class CollaboratorStatusFactory extends Factory
{
    protected $model = CollaboratorStatus::class;

    public function definition(): array
    {
        $estados = [
            ['name' => 'Activo',       'icon_class' => 'fa-regular fa-user-check'],
            ['name' => 'Extrabajador', 'icon_class' => 'fa-regular fa-user-xmark'],
            ['name' => 'Inactivo',     'icon_class' => 'fa-regular fa-user-xmark'],
            ['name' => 'Pensionado',   'icon_class' => 'fa-regular fa-island-tropical'],
            ['name' => 'Fallecido',    'icon_class' => 'fa-regular fa-tombstone'],
        ];

        $estado = $this->faker->randomElement($estados);

        return [
            'institution_id' => Institution::factory(),
            'name' => $estado['name'],
            'icon_class' => $estado['icon_class'],
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Activo',
            'icon_class' => 'fa-regular fa-user-check',
        ]);
    }
}
