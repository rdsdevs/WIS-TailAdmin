<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use App\Models\RH\Contract;
use App\Rules\MaxImportRows;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', Contract::class);
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:10240', new MaxImportRows(500)],
            'sobrescribir' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar un archivo para importar.',
            'archivo.mimes' => 'El archivo debe ser de tipo Excel (.xlsx).',
            'archivo.max' => 'El archivo no debe superar 10 MB.',
        ];
    }
}
