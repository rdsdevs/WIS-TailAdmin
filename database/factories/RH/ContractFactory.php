<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use App\Models\RH\ContractType;
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

        return [
            'institution_id' => Institution::factory(),
            'collaborator_id' => Collaborator::factory(),
            'contract_type_id' => ContractType::factory(),
            'position_id' => null,
            'contract_number' => $this->faker->optional()->numerify('####'),
            'contract_code' => $this->faker->optional()->bothify('CONT-####'),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => null,
            'object' => $this->faker->optional()->sentence(),
            'obligations' => $this->faker->optional()->sentence(),
            'salary' => $this->faker->numberBetween(1_160_000, 15_000_000),
            'fees' => 0,
            'position_email' => $this->faker->optional()->safeEmail(),
            'status' => 'Vigente',
        ];
    }

    /**
     * Contrato a término fijo con fecha de fin.
     */
    public function terminoFijo(): static
    {
        return $this->state(function (array $attributes) {
            $start = now()->subMonths(6);

            return [
                'start_date' => $start->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
                'status' => 'Vigente',
            ];
        });
    }

    /**
     * Contrato indefinido sin fecha de fin.
     */
    public function indefinido(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => null,
            'status' => 'Vigente',
        ]);
    }

    /**
     * Contrato que vence en los próximos N días.
     */
    public function venceProximamente(int $dias = 15): static
    {
        return $this->state(function (array $attributes) use ($dias) {
            return [
                'start_date' => now()->subYear()->toDateString(),
                'end_date' => now()->addDays($dias)->toDateString(),
                'status' => 'Vigente',
            ];
        });
    }

    /**
     * Contrato liquidado.
     */
    public function liquidado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Liquidado',
        ]);
    }
}
