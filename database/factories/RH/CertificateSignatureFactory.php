<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\CertificateSignature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificateSignature>
 */
class CertificateSignatureFactory extends Factory
{
    protected $model = CertificateSignature::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'signer_name' => $this->faker->name(),
            'signer_position' => $this->faker->jobTitle(),
            'signature_image' => 'firmas/'.$this->faker->uuid().'.png',
            'replacement_name' => null,
            'replacement_position' => null,
            'replacement_signature_image' => null,
            'is_active' => true,
        ];
    }

    public function withReplacement(): self
    {
        return $this->state(fn () => [
            'replacement_name' => $this->faker->name(),
            'replacement_position' => $this->faker->jobTitle(),
            'replacement_signature_image' => 'firmas/'.$this->faker->uuid().'.png',
        ]);
    }
}
