---
nombre: backend-wis
descripcion: >
  Implementar lógica de negocio backend para WIS ASCUN en Laravel 12. Usar cuando se
  creen modelos con UUID y auditoría, migrations, controllers, services, form requests,
  policies, jobs o eventos. Incluye patrones específicos para autenticación por cédula
  + fecha de expedición, multi-tenancy por institution_id, generación de PDFs/QR/Excel,
  y migración segura desde el sistema legacy app.wisascun.com.
---

# SKILL: Backend — WIS ASCUN Laravel 12

Esta guía define los patrones de implementación backend para el sistema WIS ASCUN.
Toda la lógica de negocio sigue el patrón en capas: Controller → Service → Model.

> **Autenticación WIS ASCUN:** El sistema NO usa email para login.
> Las credenciales son: **número de cédula** + **fecha de expedición del documento** + **contraseña**.

---

## Flujo de Trabajo

1. Diseñar el schema en MySQL (ver `skills/mysql-wis/SKILL.md`).
2. Crear migration con UUID, softDeletes y índices.
3. Crear Model con `HasUuidPrimaryKey` + `SoftDeletes` + `Auditable`.
4. Crear Factory para tests y seeders.
5. Crear FormRequest con mensajes en español colombiano.
6. Crear Policy para autorización.
7. Crear Service con la lógica de negocio.
8. Crear Controller delgado que delega al Service.
9. Registrar ruta con middleware `auth`.
10. Escribir tests de feature y unit.

---

## Trait HasUuidPrimaryKey

Crear **una sola vez** en `app/Models/Concerns/HasUuidPrimaryKey.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait HasUuidPrimaryKey
{
    use HasUuids;

    public function initializeHasUuidPrimaryKey(): void
    {
        $this->keyType      = 'string';
        $this->incrementing = false;
    }
}
```

---

## Modelo Base de Entidad de Negocio

```php
<?php

declare(strict_types=1);

namespace App\Models\RH;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Employee extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'institution_id',
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'email',
        'position_id',
        'contract_type',
        'start_date',
        'end_date',
        'salary',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'salary'     => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    // Accessor: nombre completo
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Relaciones
    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Auth\Institution::class);
    }

    public function position(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function contracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contract::class);
    }

    // Scopes
    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
```

---

## Autenticación WIS ASCUN

### LoginRequest

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'document_number'    => ['required', 'string', 'max:30'],
            'document_issued_at' => ['required', 'date', 'before_or_equal:today'],
            'password'           => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.required'           => 'El número de cédula es obligatorio.',
            'document_issued_at.required'        => 'La fecha de expedición es obligatoria.',
            'document_issued_at.date'            => 'La fecha de expedición no tiene un formato válido.',
            'document_issued_at.before_or_equal' => 'La fecha de expedición no puede ser futura.',
            'password.required'                  => 'La contraseña es obligatoria.',
            'password.min'                       => 'La contraseña debe tener al menos :min caracteres.',
        ];
    }
}
```

### AuthService

```php
<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    /**
     * Autentica por número de cédula + fecha de expedición + contraseña.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): User
    {
        $user = User::query()
            ->where('document_number', trim($request->input('document_number')))
            ->whereDate('document_issued_at', $request->input('document_issued_at'))
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'document_number' => 'Los datos ingresados no coinciden con ningún usuario registrado.',
            ]);
        }

        $user->update(['last_login_at' => now()]);

        return $user;
    }
}
```

---

## Service Class — Patrón

```php
<?php

declare(strict_types=1);

namespace App\Services\RH;

use App\Events\RH\EmployeeCreated;
use App\Http\Requests\RH\CreateEmployeeRequest;
use App\Models\RH\Employee;
use Illuminate\Support\Facades\DB;

final class EmployeeService
{
    public function create(CreateEmployeeRequest $request): Employee
    {
        return DB::transaction(function () use ($request): Employee {
            $employee = Employee::create($request->validated());
            event(new EmployeeCreated($employee));
            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh();
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }
}
```

---

## Controller — Patrón Delgado

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RH\CreateEmployeeRequest;
use App\Models\RH\Employee;
use App\Services\RH\EmployeeService;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $service) {}

    public function index(): \Illuminate\View\View
    {
        $this->authorize('viewAny', Employee::class);
        return view('pages.rh.empleados.index');
    }

    public function store(CreateEmployeeRequest $request): RedirectResponse
    {
        $this->service->create($request);
        return redirect()->route('rh.empleados.index')
            ->with('exito', 'Empleado registrado correctamente.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);
        $this->service->delete($employee);
        return redirect()->route('rh.empleados.index')
            ->with('exito', 'Empleado eliminado correctamente.');
    }
}
```

---

## FormRequest — Mensajes en Español Colombiano

```php
public function messages(): array
{
    return [
        'document_number.required'  => 'El número de cédula es obligatorio.',
        'document_number.unique'    => 'Ya existe un empleado con este número de cédula.',
        'first_name.required'       => 'El primer nombre es obligatorio.',
        'first_name.max'            => 'El nombre no puede tener más de :max caracteres.',
        'salary.required'           => 'El salario es obligatorio.',
        'salary.numeric'            => 'El salario debe ser un valor numérico.',
        'salary.min'                => 'El salario no puede ser negativo.',
        'start_date.required'       => 'La fecha de ingreso es obligatoria.',
        'start_date.date'           => 'La fecha de ingreso no es válida.',
        'contract_type.in'          => 'El tipo de contrato seleccionado no es válido.',
    ];
}
```

---

## Generación de PDF — Certificado Laboral

```php
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

public function descargar(Certificate $certificate): \Symfony\Component\HttpFoundation\Response
{
    $this->authorize('view', $certificate);

    $qrCode = base64_encode(
        QrCode::format('png')
            ->size(150)
            ->errorCorrection('H')
            ->generate(route('certificados.verificar', $certificate->uuid))
    );

    $pdf = Pdf::loadView('pdfs.certificado-laboral', [
        'certificado' => $certificate,
        'empleado'    => $certificate->employee,
        'qrCode'      => $qrCode,
    ])->setPaper('letter', 'portrait');

    return $pdf->download("certificado_{$certificate->employee->document_number}.pdf");
}
```

---

## Exportación Excel

```php
<?php

declare(strict_types=1);

namespace App\Exports\RH;

use App\Models\RH\Employee;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EmpleadosExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly string $institutionId) {}

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Employee::query()
            ->with('position')
            ->where('institution_id', $this->institutionId)
            ->where('is_active', true)
            ->orderBy('last_name');
    }

    public function headings(): array
    {
        return ['Cédula', 'Nombre completo', 'Cargo', 'Salario', 'Fecha de ingreso', 'Tipo de contrato'];
    }

    public function map($empleado): array
    {
        return [
            $empleado->document_number,
            $empleado->full_name,
            $empleado->position->name,
            '$ ' . number_format($empleado->salary, 2, ',', '.'),
            $empleado->start_date->format('d/m/Y'),
            match($empleado->contract_type) {
                'indefinite'  => 'Término indefinido',
                'fixed_term'  => 'Término fijo',
                'contractor'  => 'Contratista',
                default       => $empleado->contract_type,
            },
        ];
    }
}
```

---

## Roles y Permisos (Spatie)

```php
// Seeder de roles
$roles = [
    'super-admin', 'admin',
    'rh-manager', 'rh-viewer',
    'accounting-manager', 'accounting-viewer',
    'inventory-manager', 'inventory-viewer',
];

foreach ($roles as $rol) {
    Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
}

// Asignar en Policy
public function create(User $user): bool
{
    return $user->hasRole(['super-admin', 'admin', 'rh-manager']);
}

// En Controller
$this->authorize('create', Employee::class);

// En Blade
@can('create', App\Models\RH\Employee::class)
    <a href="{{ route('rh.empleados.create') }}">Nuevo empleado</a>
@endcan
```

---

## Guardrails

1. `declare(strict_types=1)` en todos los archivos PHP.
2. Type hints en parámetros y valores de retorno.
3. **Nunca raw SQL** con variables interpoladas.
4. **Nunca lógica de negocio en Controllers** — todo en Services.
5. **Nunca lógica en Models** — solo scopes, mutators, relaciones, casts.
6. `DB::transaction` en toda operación multi-tabla.
7. `$fillable` explícito en todos los modelos — nunca `$guarded = []`.
8. `password_hash` solo vía `Hash::make()` o `protected $casts = ['password' => 'hashed']`.
9. Mensajes al usuario siempre en **español colombiano**.
10. Solicitar aprobación antes de operaciones destructivas en datos de RH, contabilidad o inventario.
