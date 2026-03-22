<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collaborator>
 */
class CollaboratorFactory extends Factory
{
    protected $model = Collaborator::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'document_type_id' => DocumentType::factory(),
            'document_number' => $this->faker->unique()->numerify('##########'),
            'document_issued_at' => $this->faker->dateTimeBetween('-30 years', '-1 year')->format('Y-m-d'),
            'first_name' => $this->faker->firstName(),
            'second_name' => $this->faker->optional(0.4)->firstName(),
            'first_surname' => $this->faker->lastName(),
            'second_surname' => $this->faker->optional(0.6)->lastName(),
            'birth_date' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['F', 'M']),
            'is_company' => false,
            'company_name' => null,
            'legal_representative' => null,
            'email' => $this->faker->optional()->safeEmail(),
            'phone' => $this->faker->optional()->numerify('3##-###-####'),
            'address' => $this->faker->optional()->address(),
            'type' => 'Empleado',
            'status_id' => CollaboratorStatus::factory(),
        ];
    }

    /**
     * Colaborador de tipo Contratista.
     */
    public function contratista(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Contratista',
        ]);
    }

    /**
     * Colaborador empresa (persona jurídica).
     */
    public function empresa(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_company' => true,
            'company_name' => $this->faker->company(),
            'legal_representative' => $this->faker->name(),
            'type' => 'Contratista',
        ]);
    }
}
