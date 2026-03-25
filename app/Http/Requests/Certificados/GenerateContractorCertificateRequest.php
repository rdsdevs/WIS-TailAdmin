<?php

declare(strict_types=1);

namespace App\Http\Requests\Certificados;

use App\Models\RH\CertificateSignature;
use App\Models\RH\Collaborator;
use App\Models\RH\Contract;
use Illuminate\Foundation\Http\FormRequest;

class GenerateContractorCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'collaborator_id'                   => ['required', 'uuid', 'exists:collaborators,id'],
            'certificate_signature_id'          => ['required', 'uuid', 'exists:certificate_signatures,id'],
            'contract_ids'                      => ['required', 'array', 'min:1'],
            'contract_ids.*'                    => ['uuid', 'exists:contracts,id'],
            'options.show_object'               => ['boolean'],
            'options.show_obligations'          => ['boolean'],
            'options.show_value'                => ['boolean'],
            'options.show_prorrogas'            => ['boolean'],
            'options.show_early_termination'    => ['boolean'],
            'addressed_to'                      => ['nullable', 'string', 'max:255'],
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

            $collaborator = Collaborator::find($this->collaborator_id);
            if ($collaborator && $collaborator->institution_id !== $institutionId) {
                $v->errors()->add('collaborator_id', 'El colaborador no pertenece a su institución.');

                return;
            }

            if ($collaborator && $collaborator->type !== 'Contratista') {
                $v->errors()->add('collaborator_id', 'Este módulo solo genera certificados para contratistas.');

                return;
            }

            $signature = CertificateSignature::find($this->certificate_signature_id);
            if ($signature && $signature->institution_id !== $institutionId) {
                $v->errors()->add('certificate_signature_id', 'La firma seleccionada no pertenece a su institución.');
            }

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
