<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-3 years', 'now');
        $type = $this->faker->randomElement(['indefinite', 'fixed_term', 'contractor', 'intern']);
        $endDate = in_array($type, ['fixed_term', 'intern'], true)
            ? $this->faker->dateTimeBetween($startDate, '+2 years')->format('Y-m-d')
            : null;

        return [
            'institution_id' => Institution::factory(),
            'contractable_id' => Employee::factory(),
            'contractable_type' => Employee::class,
            'contract_type' => $type,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate,
            'salary' => $this->faker->numberBetween(1_160_000, 15_000_000),
            'position' => $this->faker->jobTitle(),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function terminoFijo(): static
    {
        return $this->state(function (array $attributes) {
            $start = now()->subMonths(6);

            return [
                'contract_type' => 'fixed_term',
                'start_date' => $start->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
            ];
        });
    }

    public function indefinido(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_type' => 'indefinite',
            'end_date' => null,
        ]);
    }

    public function venceProximamente(int $dias = 15): static
    {
        return $this->state(function (array $attributes) use ($dias) {
            return [
                'contract_type' => 'fixed_term',
                'start_date' => now()->subYear()->toDateString(),
                'end_date' => now()->addDays($dias)->toDateString(),
                'is_active' => true,
            ];
        });
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
