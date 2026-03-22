<?php

use Livewire\Component;
use App\Models\RH\Collaborator;
use App\Models\RH\CollaboratorStatus;
use App\Models\RH\DocumentType;

new class extends Component {

    // ── Props ─────────────────────────────────────────────────────────────────
    public ?string $collaboratorId = null;

    // ── Sección: tipo --────────────────────────────────────────────────────────
    public string $type      = 'Empleado'; // Empleado | Contratista
    public bool   $isCompany = false;

    // ── Sección: datos personales ─────────────────────────────────────────────
    public string $documentTypeId   = '';
    public string $documentNumber   = '';
    public string $documentIssuedAt = '';
    public string $firstName        = '';
    public string $secondName       = '';
    public string $firstSurname     = '';
    public string $secondSurname    = '';
    public string $birthDate        = '';
    public string $gender           = '';

    // ── Sección: empresa ──────────────────────────────────────────────────────
    public string $companyName         = '';
    public string $legalRepresentative = '';

    // ── Sección: contacto ─────────────────────────────────────────────────────
    public string $email   = '';
    public string $phone   = '';
    public string $address = '';

    // ── Sección: estado ───────────────────────────────────────────────────────
    public string $statusId = '';

    // ── Paso del formulario ───────────────────────────────────────────────────
    public int $step = 1; // 1: Tipo | 2: Datos personales | 3: Contacto

    public function mount(?string $collaboratorId = null): void
    {
        $this->collaboratorId = $collaboratorId;

        if ($collaboratorId) {
            $c = Collaborator::findOrFail($collaboratorId);

            $this->type              = $c->type ?? 'Empleado';
            $this->isCompany         = (bool) $c->is_company;
            $this->documentTypeId    = (string) ($c->document_type_id ?? '');
            $this->documentNumber    = $c->document_number ?? '';
            $this->documentIssuedAt  = $c->document_issued_at?->format('Y-m-d') ?? '';
            $this->firstName         = $c->first_name ?? '';
            $this->secondName        = $c->second_name ?? '';
            $this->firstSurname      = $c->first_surname ?? '';
            $this->secondSurname     = $c->second_surname ?? '';
            $this->birthDate         = $c->birth_date?->format('Y-m-d') ?? '';
            $this->gender            = $c->gender ?? '';
            $this->companyName       = $c->company_name ?? '';
            $this->legalRepresentative = $c->legal_representative ?? '';
            $this->email             = $c->email ?? '';
            $this->phone             = $c->phone ?? '';
            $this->address           = $c->address ?? '';
            $this->statusId          = (string) ($c->status_id ?? '');
        }
    }

    protected function rules(): array
    {
        $rules = [
            'type'     => 'required|in:Empleado,Contratista',
            'statusId' => 'required|uuid',
            'email'    => 'nullable|email|max:200',
            'phone'    => 'nullable|string|max:50',
            'address'  => 'nullable|string|max:300',
        ];

        if ($this->isCompany) {
            $rules['companyName']         = 'required|string|max:200';
            $rules['legalRepresentative'] = 'nullable|string|max:200';
        } else {
            $rules['documentTypeId']   = 'required|uuid';
            $rules['documentNumber']   = 'required|string|min:6|max:20';
            $rules['documentIssuedAt'] = 'nullable|date';
            $rules['firstName']        = 'required|string|max:100';
            $rules['firstSurname']     = 'required|string|max:100';
            $rules['secondName']       = 'nullable|string|max:100';
            $rules['secondSurname']    = 'nullable|string|max:100';
            $rules['birthDate']        = 'nullable|date';
            $rules['gender']           = 'nullable|in:M,F,Otro';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'type.required'           => 'Seleccione el tipo de colaborador.',
            'statusId.required'       => 'Seleccione el estado del colaborador.',
            'companyName.required'    => 'La razón social es obligatoria.',
            'documentTypeId.required' => 'Seleccione el tipo de documento.',
            'documentNumber.required' => 'El número de documento es obligatorio.',
            'documentNumber.min'      => 'El documento debe tener al menos 6 caracteres.',
            'firstName.required'      => 'El nombre es obligatorio.',
            'firstSurname.required'   => 'El primer apellido es obligatorio.',
            'email.email'             => 'Ingrese un correo electrónico válido.',
        ];
    }

    public function updatedIsCompany(): void
    {
        // Limpiar campos mutuamente excluyentes al cambiar el toggle
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'institution_id' => auth()->user()->institution_id,
            'type'           => $this->type,
            'is_company'     => $this->isCompany,
            'status_id'      => $this->statusId,
            'email'          => $this->email ?: null,
            'phone'          => $this->phone ?: null,
            'address'        => $this->address ?: null,
        ];

        if ($this->isCompany) {
            $data['company_name']         = $this->companyName;
            $data['legal_representative'] = $this->legalRepresentative ?: null;
            // Limpiar campos personales (no aplican para empresas)
            $data['document_type_id'] = null;
            $data['first_name']       = null;
            $data['first_surname']    = null;
            $data['document_number']  = null;
        } else {
            $data['document_type_id']   = $this->documentTypeId;
            $data['document_number']    = $this->documentNumber;
            $data['document_issued_at'] = $this->documentIssuedAt ?: null;
            $data['first_name']         = $this->firstName;
            $data['second_name']        = $this->secondName ?: null;
            $data['first_surname']      = $this->firstSurname;
            $data['second_surname']     = $this->secondSurname ?: null;
            $data['birth_date']         = $this->birthDate ?: null;
            $data['gender']             = $this->gender ?: null;
        }

        if ($this->collaboratorId) {
            $this->authorize('update', Collaborator::findOrFail($this->collaboratorId));
            Collaborator::findOrFail($this->collaboratorId)->update($data);
            session()->flash('success', 'Colaborador actualizado correctamente.');
            $this->redirect(route('rh.colaboradores.show', $this->collaboratorId), navigate: false);
        } else {
            $this->authorize('create', Collaborator::class);
            $collaborator = Collaborator::create($data);
            session()->flash('success', 'Colaborador registrado correctamente.');
            $this->redirect(route('rh.colaboradores.show', $collaborator->id), navigate: false);
        }
    }

    public function getDocumentTypesProperty()
    {
        return DocumentType::orderBy('name')->get();
    }

    public function getStatusesProperty()
    {
        return CollaboratorStatus::orderBy('name')->get();
    }
};
?>

<div class="mx-auto max-w-2xl">
    {{-- Indicador de pasos --}}
    <div class="mb-8 flex items-center justify-between">
        @foreach([1 => 'Tipo', 2 => 'Datos', 3 => 'Contacto'] as $n => $label)
            <div class="flex flex-1 items-center {{ $n < 3 ? 'after:h-px after:flex-1 after:bg-gray-200 dark:after:bg-gray-700' : '' }}">
                <button
                    wire:click="$set('step', {{ $n }})"
                    class="flex items-center gap-2 focus:outline-none"
                    aria-label="Ir al paso {{ $n }}: {{ $label }}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold
                        {{ $step === $n
                            ? 'bg-blue-600 text-white'
                            : ($step > $n ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400') }}">
                        @if($step > $n)
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        @else
                            {{ $n }}
                        @endif
                    </span>
                    <span class="hidden text-xs font-medium sm:block
                        {{ $step === $n ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $label }}
                    </span>
                </button>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

        {{-- PASO 1: Tipo de colaborador --}}
        @if($step === 1)
            <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Tipo de colaborador</h2>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Seleccione si este colaborador es un empleado de nómina o un contratista.</p>

            <div class="grid grid-cols-2 gap-4">
                {{-- Empleado --}}
                <button
                    type="button"
                    wire:click="$set('type', 'Empleado')"
                    class="group flex flex-col items-center gap-3 rounded-xl border-2 p-6 transition-colors focus:outline-none
                        {{ $type === 'Empleado'
                            ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                            : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' }}"
                    aria-pressed="{{ $type === 'Empleado' ? 'true' : 'false' }}">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full
                        {{ $type === 'Empleado' ? 'bg-blue-100 dark:bg-blue-900/40' : 'bg-gray-100 dark:bg-gray-700' }}">
                        <svg class="h-7 w-7 {{ $type === 'Empleado' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="font-semibold text-gray-900 dark:text-white">Empleado</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Vinculado por nómina</p>
                    </div>
                </button>

                {{-- Contratista --}}
                <button
                    type="button"
                    wire:click="$set('type', 'Contratista')"
                    class="group flex flex-col items-center gap-3 rounded-xl border-2 p-6 transition-colors focus:outline-none
                        {{ $type === 'Contratista'
                            ? 'border-purple-500 bg-purple-50 dark:border-purple-400 dark:bg-purple-900/20'
                            : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' }}"
                    aria-pressed="{{ $type === 'Contratista' ? 'true' : 'false' }}">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full
                        {{ $type === 'Contratista' ? 'bg-purple-100 dark:bg-purple-900/40' : 'bg-gray-100 dark:bg-gray-700' }}">
                        <svg class="h-7 w-7 {{ $type === 'Contratista' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400 dark:text-gray-500' }}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="font-semibold text-gray-900 dark:text-white">Contratista</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Persona natural o empresa</p>
                    </div>
                </button>
            </div>

            {{-- Toggle persona / empresa (solo contratistas) --}}
            @if($type === 'Contratista')
                <div class="mt-5 flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-700/50">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">¿Es persona jurídica (empresa)?</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Active si el contratista es una empresa o persona jurídica.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="$toggle('isCompany')"
                        role="switch"
                        aria-checked="{{ $isCompany ? 'true' : 'false' }}"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                            {{ $isCompany ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition-transform
                            {{ $isCompany ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    wire:click="$set('step', 2)"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Siguiente
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- PASO 2: Datos personales / empresa --}}
        @if($step === 2)
            @if($isCompany)
                <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Datos de la empresa</h2>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Información de la persona jurídica.</p>

                <div class="space-y-4">
                    <div>
                        <label for="companyName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Razón social <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            wire:model.blur="companyName"
                            id="companyName"
                            type="text"
                            placeholder="Ej: Empresa ABC S.A.S."
                            class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                        />
                        @error('companyName')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="legalRepresentative" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Representante legal
                        </label>
                        <input
                            wire:model.blur="legalRepresentative"
                            id="legalRepresentative"
                            type="text"
                            placeholder="Nombre del representante legal"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                        />
                    </div>

                    <div>
                        <label for="documentNumber" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            NIT
                        </label>
                        <input
                            wire:model.blur="documentNumber"
                            id="documentNumber"
                            type="text"
                            placeholder="Ej: 900123456-1"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                        />
                    </div>
                </div>
            @else
                <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Datos personales</h2>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Información básica del colaborador.</p>

                <div class="space-y-4">
                    {{-- Tipo y número de documento --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="documentTypeId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tipo de documento <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <select
                                wire:model="documentTypeId"
                                id="documentTypeId"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">Seleccione...</option>
                                @foreach($this->documentTypes as $dt)
                                    <option value="{{ $dt->id }}">{{ $dt->name }} ({{ $dt->code }})</option>
                                @endforeach
                            </select>
                            @error('documentTypeId')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="documentNumber" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Número de documento <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                wire:model.blur="documentNumber"
                                id="documentNumber"
                                type="text"
                                placeholder="Ej: 1234567890"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                            />
                            @error('documentNumber')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Fecha de expedición --}}
                    <div>
                        <label for="documentIssuedAt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Fecha de expedición del documento
                        </label>
                        <input
                            wire:model="documentIssuedAt"
                            id="documentIssuedAt"
                            type="date"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:scheme-dark"
                        />
                    </div>

                    {{-- Nombres --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Primer nombre <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                wire:model.blur="firstName"
                                id="firstName"
                                type="text"
                                placeholder="Ej: Carlos"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                            />
                            @error('firstName')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="secondName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Segundo nombre
                                <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(opcional)</span>
                            </label>
                            <input
                                wire:model.blur="secondName"
                                id="secondName"
                                type="text"
                                placeholder="Ej: Andrés"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                            />
                        </div>
                    </div>

                    {{-- Apellidos --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="firstSurname" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Primer apellido <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                wire:model.blur="firstSurname"
                                id="firstSurname"
                                type="text"
                                placeholder="Ej: Gómez"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                            />
                            @error('firstSurname')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="secondSurname" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Segundo apellido
                                <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(opcional)</span>
                            </label>
                            <input
                                wire:model.blur="secondSurname"
                                id="secondSurname"
                                type="text"
                                placeholder="Ej: Rodríguez"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                            />
                        </div>
                    </div>

                    {{-- Fecha nacimiento y género --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="birthDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Fecha de nacimiento
                            </label>
                            <input
                                wire:model="birthDate"
                                id="birthDate"
                                type="date"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Género
                            </label>
                            <select
                                wire:model="gender"
                                id="gender"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">Seleccione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-between">
                <button type="button" wire:click="$set('step', 1)"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Anterior
                </button>
                <button type="button" wire:click="$set('step', 3)"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Siguiente
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- PASO 3: Contacto y estado --}}
        @if($step === 3)
            <h2 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Datos de contacto y estado</h2>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Información de contacto y estado del colaborador en el sistema.</p>

            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Correo electrónico
                    </label>
                    <input
                        wire:model.blur="email"
                        id="email"
                        type="email"
                        placeholder="correo@ejemplo.com"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Teléfono
                    </label>
                    <input
                        wire:model.blur="phone"
                        id="phone"
                        type="tel"
                        placeholder="Ej: 3001234567"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    />
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Dirección
                    </label>
                    <input
                        wire:model.blur="address"
                        id="address"
                        type="text"
                        placeholder="Ej: Cra 10 # 20-30, Bogotá"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-500"
                    />
                </div>

                <div>
                    <label for="statusId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Estado <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select
                        wire:model="statusId"
                        id="statusId"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Seleccione el estado...</option>
                        @foreach($this->statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    @error('statusId')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-between">
                <button type="button" wire:click="$set('step', 2)"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Anterior
                </button>
                <button
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
                    <span wire:loading wire:target="save">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="save">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                    {{ $collaboratorId ? 'Actualizar colaborador' : 'Registrar colaborador' }}
                </button>
            </div>
        @endif
    </div>
</div>
