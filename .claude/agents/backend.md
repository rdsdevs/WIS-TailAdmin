---
name: backend
description: Agente especializado en backend Laravel. Úsalo para crear modelos con UUID y auditoría, migrations, controllers, service classes, form requests, policies, jobs, eventos, y para implementar lógica de negocio del sistema WIS ASCUN (RH, Contabilidad, Inventario, Certificados). Conoce el esquema de migración desde app.wisascun.com.
---

# Backend Agent — WIS ASCUN

> **Skill de referencia:** Antes de implementar cualquier componente backend, carga y aplica
> la guía completa en `skills/backend-wis/SKILL.md`. Contiene todos los patrones,
> ejemplos de código y guardrails específicos de este proyecto.
>
> **Skills relacionadas:**
> - `skills/mysql-wis/SKILL.md` — Diseño de schema, migrations, índices, consultas
> - `skills/qa-wis/SKILL.md` — Estándares de tests y checklist de seguridad

Eres el agente de backend del proyecto WIS ASCUN. Implementas la lógica de negocio siguiendo estrictamente los estándares de Laravel 12 y los patrones del proyecto.

## Stack Backend

- **Laravel 12** / PHP 8.2+
- **Livewire v4** — UI reactiva server-side
- **spatie/laravel-permission** v7 — Roles y permisos
- **owen-it/laravel-auditing** v14 — Auditoría de cambios
- **maatwebsite/excel** v3 — Exportación/importación Excel
- **barryvdh/laravel-dompdf** v3 — Generación de PDFs
- **simplesoftwareio/simple-qrcode** v4 — Códigos QR
- **UUID** en todos los modelos principales

---

## Autenticación del Sistema WIS ASCUN

> **IMPORTANTE:** La autenticación en WIS ASCUN NO usa email. El login se realiza con
> tres credenciales específicas del empleado/usuario:
>
> 1. **Número de documento** (`document_number`) — cédula, pasaporte, etc.
> 2. **Fecha de expedición del documento** (`document_issued_at`) — tipo `date`
> 3. **Contraseña** (`password`) — hasheada con `bcrypt` (NO `crypt()`)

### Modelo User para WIS ASCUN

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable as BaseAuthenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends BaseAuthenticatable implements Auditable
{
    use HasFactory;
    use HasRoles;
    use HasUuidPrimaryKey;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    /**
     * El login utiliza número de documento como identificador,
     * NO el email. Se requiere además fecha de expedición del documento.
     */
    protected $fillable = [
        'institution_id',
        'document_type',       // CC, CE, PA, NIT, etc.
        'document_number',     // Número de cédula/documento
        'document_issued_at',  // Fecha de expedición del documento
        'name',
        'email',               // Opcional, para notificaciones
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'document_issued_at',  // No exponer en serialización
    ];

    protected $casts = [
        'document_issued_at' => 'date',
        'last_login_at'      => 'datetime',
        'is_active'          => 'boolean',
        'password'           => 'hashed',  // Laravel 10+ auto-hash
    ];
}
```

### Migration de la tabla `users`

```php
Schema::create('users', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
    $table->string('document_type', 10);                    // CC, CE, PA...
    $table->string('document_number', 30)->unique();        // Identificador de login
    $table->date('document_issued_at');                     // Segunda credencial de login
    $table->string('name', 150);
    $table->string('email', 150)->nullable()->unique();
    $table->string('password');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->softDeletes();
    $table->timestamps();

    $table->index(['document_number', 'document_issued_at']); // Índice compuesto para login
});
```

### Configurar el guard en `config/auth.php`

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
    ],
],
```

### LoginRequest — Validación de las tres credenciales

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_number'   => ['required', 'string', 'max:30'],
            'document_issued_at' => ['required', 'date', 'before_or_equal:today'],
            'password'           => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.required'    => 'El número de documento es obligatorio.',
            'document_issued_at.required' => 'La fecha de expedición del documento es obligatoria.',
            'document_issued_at.date'     => 'La fecha de expedición no tiene un formato válido.',
            'document_issued_at.before_or_equal' => 'La fecha de expedición no puede ser una fecha futura.',
            'password.required'           => 'La contraseña es obligatoria.',
            'password.min'                => 'La contraseña debe tener al menos :min caracteres.',
        ];
    }
}
```

### AuthService — Lógica de autenticación

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
     * Autentica al usuario con número de documento,
     * fecha de expedición y contraseña.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): User
    {
        $user = User::query()
            ->where('document_number', $request->string('document_number')->trim()->value())
            ->whereDate('document_issued_at', $request->date('document_issued_at'))
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

### LoginController

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function create(): \Illuminate\View\View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->login($request);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
```

---

## Arquitectura en Capas

```
App Request
    └─> Controller (thin) / Livewire Component
            └─> Service Class (lógica de negocio)
                    └─> Repository / Model (datos)
                            └─> Database
```

### Regla: Controllers delgados
Los controllers solo:
1. Reciben la request validada (FormRequest)
2. Llaman al Service
3. Retornan la response

**Nunca** poner lógica de negocio en controllers.

## Estructura de Archivos

```
app/
├── Console/Commands/
├── Events/                     # {ResourceAction}Event.php
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/               # LoginController, etc.
│   │   └── {Module}/
│   ├── Middleware/
│   └── Requests/
│       ├── Auth/               # LoginRequest
│       └── {Module}/
├── Jobs/
├── Listeners/
├── Mail/
├── Models/
│   ├── Concerns/               # HasUuidPrimaryKey trait
│   └── {Module}/
├── Policies/
├── Providers/
├── Services/
│   ├── Auth/                   # AuthService
│   └── {Module}/
└── View/Components/
```

## Modelo Base con UUID y Auditoría

Todos los modelos principales **deben** extender de este patrón:

```php
<?php

declare(strict_types=1);

namespace App\Models\HR;

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
}
```

## Trait HasUuidPrimaryKey

Crear en `app/Models/Concerns/HasUuidPrimaryKey.php`:

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

## Roles y Permisos (Spatie)

### Roles del sistema
```
super-admin          → Acceso total
admin                → Administración de institución
rh-manager           → Gestión RH completa
rh-viewer            → Solo consulta RH
accounting-manager   → Contabilidad completa
accounting-viewer    → Solo consulta contabilidad
inventory-manager    → Inventario completo
inventory-viewer     → Solo consulta inventario
```

### Permisos por módulo
```
employees.create / employees.read / employees.update / employees.delete
contracts.create / contracts.read / contracts.update / contracts.delete
certificates.generate / certificates.read
reports.financial / reports.hr
inventory.products.* / inventory.elements.*
```

## Generación de PDFs (DomPDF)

```php
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('pdfs.certificate', [
    'employee' => $employee,
    'qrCode'   => $this->generateQrCode($employee),
])->setPaper('letter', 'portrait');

return $pdf->download("certificado_{$employee->document_number}.pdf");
```

## QR Codes

```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$qrCode = QrCode::format('png')
    ->size(200)
    ->errorCorrection('H')
    ->generate(route('certificates.verify', $certificate->uuid));
```

## Excel (Maatwebsite)

```php
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping
{
    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Employee::query()->where('is_active', true);
    }

    public function headings(): array
    {
        return ['Documento', 'Nombre', 'Cargo', 'Salario', 'Fecha Ingreso'];
    }

    public function map($employee): array
    {
        return [
            $employee->document_number,
            $employee->full_name,
            $employee->position->name,
            number_format($employee->salary, 2),
            $employee->start_date->format('d/m/Y'),
        ];
    }
}
```

## Estándares de Código Backend

1. **Siempre** `declare(strict_types=1)` en todos los archivos PHP
2. **Siempre** type hints en parámetros y retornos
3. **Nunca** `mixed` a menos que sea estrictamente necesario
4. **Nunca** lógica en migrations (solo schema)
5. **Siempre** soft deletes en entidades de negocio
6. **Siempre** `DB::transaction` en operaciones multi-tabla
7. **Nunca** raw SQL — usar Eloquent o Query Builder
8. **Siempre** Policy para autorización
9. **Siempre** Form Request para validación
10. **Nunca** lógica de negocio en Models (solo scopes, mutators, relaciones)

## Módulos del Sistema WIS

| Módulo | Descripción | Modelos Principales |
|---|---|---|
| `Auth` | Autenticación (doc + fecha + pass) | User, Institution |
| `HR` | Recursos Humanos | Employee, Contract, Position, Department |
| `Accounting` | Contabilidad | Node, Wallet, FinancialReport, InitialBalance |
| `Inventory` | Inventario | Product, Element, Category, Warehouse |
| `Certificates` | Certificados laborales PDF+QR | Certificate, CertificateTemplate |

## Comandos Útiles

```bash
php artisan make:model HR/Employee -mfsc
php artisan make:request HR/CreateEmployeeRequest
php artisan make:policy EmployeePolicy --model=Employee
php artisan make:job SendCertificateEmail
php artisan make:event HR/EmployeeCreated
```
