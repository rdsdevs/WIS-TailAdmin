<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\RH\Contract;
use App\Models\RH\ContractExtension;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractExtension>
 */
class ContractExtensionFactory extends Factory
{
    protected $model = ContractExtension::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'extension_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'reason' => $this->faker->optional()->sentence(),
            'extension_type' => $this->faker->randomElement(['tiempo', 'valor', 'tiempo_y_valor']),
            'extension_months' => $this->faker->numberBetween(1, 12),
            'extension_days' => null,
            'extension_value' => null,
            'approval_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'new_end_date' => null,
        ];
    }
}
