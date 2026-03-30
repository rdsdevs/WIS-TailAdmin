<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\RH\CommittedValue;
use App\Models\RH\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommittedValue>
 */
class CommittedValueFactory extends Factory
{
    protected $model = CommittedValue::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'accounting_account' => $this->faker->numerify('####-##'),
            'cost_center' => $this->faker->numerify('CC-###'),
            'amount' => $this->faker->numberBetween(1_000_000, 50_000_000),
        ];
    }
}
