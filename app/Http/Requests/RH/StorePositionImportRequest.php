<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use App\Models\RH\Position;
use App\Rules\MaxImportRows;
use Illuminate\Foundation\Http\FormRequest;

class StorePositionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', Position::class);
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:xlsx,csv', 'max:2048', new MaxImportRows(500)],
            'tipo' => ['required', 'in:cargos,funciones,correos'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar un archivo para importar.',
            'archivo.mimes' => 'El archivo debe ser de tipo Excel (.xlsx) o CSV (.csv).',
            'archivo.max' => 'El archivo no debe superar 2 MB.',
            'tipo.required' => 'Debe seleccionar el tipo de importación.',
            'tipo.in' => 'El tipo de importación debe ser cargos, funciones o correos.',
        ];
    }
}
