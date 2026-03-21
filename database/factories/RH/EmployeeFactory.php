<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\Department;
use App\Models\RH\Employee;
use App\Models\RH\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'position_id' => Position::factory(),
            'department_id' => Department::factory(),
            'document_type' => $this->faker->randomElement(['CC', 'CE', 'PA', 'TI']),
            'document_number' => $this->faker->unique()->numerify('##########'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->numerify('3##-###-####'),
            'birth_date' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'address' => $this->faker->optional()->address(),
            'salary' => $this->faker->numberBetween(1_160_000, 15_000_000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function cedula(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'CC',
        ]);
    }
}
