<?php

declare(strict_types=1);

namespace App\Http\Requests\RH;

use App\Models\RH\Collaborator;
use App\Rules\MaxImportRows;
use Illuminate\Foundation\Http\FormRequest;

class StoreCollaboratorImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', [Collaborator::class, $this->input('tipo')]);
    }

    public function rules(): array
    {
        return [
            'archivo'      => ['required', 'file', 'mimes:xlsx,csv', 'max:5120', new MaxImportRows(500)],
            'tipo'         => ['required', 'in:Empleado,Contratista'],
            'sobrescribir' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Seleccione un archivo para importar.',
            'archivo.file'     => 'El archivo no es válido.',
            'archivo.mimes'    => 'Solo se permiten archivos .xlsx o .csv.',
            'archivo.max'      => 'El archivo no puede superar 5 MB.',
            'tipo.required'    => 'Seleccione el tipo de colaborador.',
            'tipo.in'          => 'El tipo debe ser Empleado o Contratista.',
        ];
    }
}
