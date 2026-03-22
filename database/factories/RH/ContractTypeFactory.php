<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\ContractType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractType>
 */
class ContractTypeFactory extends Factory
{
    protected $model = ContractType::class;

    public function definition(): array
    {
        $tipos = [
            ['code' => 'IDFD', 'name' => 'Indefinido'],
            ['code' => 'FIAA', 'name' => 'Fijo Inferior a un año'],
            ['code' => 'OPS',  'name' => 'OPS'],
            ['code' => 'CPS',  'name' => 'Prestador de Servicios'],
            ['code' => 'PTCT', 'name' => 'Practicante'],
        ];

        $tipo = $this->faker->randomElement($tipos);

        return [
            'institution_id' => Institution::factory(),
            'code' => $tipo['code'],
            'name' => $tipo['name'],
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    public function indefinido(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'IDFD',
            'name' => 'Indefinido',
        ]);
    }

    public function ops(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'OPS',
            'name' => 'OPS',
        ]);
    }
}
