<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Maatwebsite\Excel\Facades\Excel;

class MaxImportRows implements ValidationRule
{
    public function __construct(private readonly int $max = 500) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $count = Excel::toCollection(null, $value)->first()?->count() ?? 0;
            if ($count > $this->max) {
                $fail("El archivo no puede contener más de {$this->max} filas (sin contar el encabezado).");
            }
        } catch (\Exception) {
            // Si no se puede leer, la validación de mimes lo capturará
        }
    }
}
