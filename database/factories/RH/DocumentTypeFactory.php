<?php

declare(strict_types=1);

namespace Database\Factories\RH;

use App\Models\Institution;
use App\Models\RH\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        // Generamos un código único para evitar violaciones de unicidad (institution_id, code)
        // cuando múltiples factories crean DocumentType para la misma institución en tests.
        static $counter = 0;
        $counter++;

        return [
            'institution_id' => Institution::factory(),
            'code' => 'T'.str_pad((string) $counter, 3, '0', STR_PAD_LEFT),
            'name' => 'Tipo de Documento '.$counter,
        ];
    }

    public function cedulaCiudadania(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'CC',
            'name' => 'Cédula de Ciudadanía',
        ]);
    }
}
