<?php

declare(strict_types=1);

namespace App\Http\Requests\Certificados;

use App\Models\RH\CertificateSignature;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateEmployeeCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización manejada en el controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'collaborator_id'            => ['required', 'uuid', 'exists:collaborators,id'],
            'certificate_signature_id'   => ['required', 'uuid', 'exists:certificate_signatures,id'],
            'contract_ids'               => ['required', 'array', 'min:1'],
            'contract_ids.*'             => ['uuid', 'exists:contracts,id'],
            'options.show_salary'        => ['boolean'],
            'options.show_position_history' => ['boolean'],
            'addressed_to'               => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'collaborator_id.required'          => 'Debe seleccionar un colaborador.',
            'certificate_signature_id.required' => 'Debe seleccionar una firma.',
            'contract_ids.required'             => 'Debe seleccionar al menos un contrato.',
            'contract_ids.min'                  => 'Debe seleccionar al menos un contrato.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $institutionId = auth()->user()?->institution_id;

            // IDOR: verificar que el colaborador pertenece a la institución
            $collaborator = Collaborator::find($this->collaborator_id);
            if ($collaborator && $collaborator->institution_id !== $institutionId) {
                $v->errors()->add('collaborator_id', 'El colaborador no pertenece a su institución.');

                return;
            }

            // Verificar que el colaborador es de tipo Empleado
            if ($collaborator && $collaborator->type !== 'Empleado') {
                $v->errors()->add('collaborator_id', 'Este módulo solo genera certificados para empleados.');

                return;
            }

            // IDOR: verificar que la firma pertenece a la institución
            $signature = CertificateSignature::find($this->certificate_signature_id);
            if ($signature && $signature->institution_id !== $institutionId) {
                $v->errors()->add('certificate_signature_id', 'La firma seleccionada no pertenece a su institución.');
            }

            // Verificar que todos los contratos pertenecen al colaborador
            if ($this->contract_ids && $collaborator) {
                $validCount = Contract::whereIn('id', $this->contract_ids)
                    ->where('collaborator_id', $collaborator->id)
                    ->count();

                if ($validCount !== count($this->contract_ids)) {
                    $v->errors()->add('contract_ids', 'Uno o más contratos seleccionados no pertenecen al colaborador.');
                }
            }
        });
    }
}
