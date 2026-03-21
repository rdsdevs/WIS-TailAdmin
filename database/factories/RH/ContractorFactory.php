<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\Contractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contractor>
 */
class ContractorFactory extends Factory
{
    protected $model = Contractor::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'document_type' => $this->faker->randomElement(['CC', 'CE', 'NIT', 'PA']),
            'document_number' => $this->faker->unique()->numerify('##########'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'company_name' => $this->faker->optional(0.3)->company(),
            'email' => $this->faker->optional()->safeEmail(),
            'phone' => $this->faker->optional()->numerify('3##-###-####'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function empresa(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'NIT',
            'company_name' => $this->faker->company(),
        ]);
    }
}
