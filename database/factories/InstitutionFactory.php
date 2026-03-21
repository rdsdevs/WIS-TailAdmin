<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        $universidades = [
            'Universidad Nacional de Colombia',
            'Universidad de Antioquia',
            'Universidad de los Andes',
            'Pontificia Universidad Javeriana',
            'Universidad del Valle',
            'Universidad de Caldas',
            'Universidad Pedagógica Nacional',
            'Universidad Distrital Francisco José de Caldas',
            'Universidad Tecnológica de Pereira',
            'Universidad de Cartagena',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($universidades),
            'nit' => $this->faker->unique()->numerify('########-#'),
            'city' => $this->faker->randomElement(['Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Manizales', 'Pereira', 'Cartagena']),
            'is_active' => true,
        ];
    }
}
