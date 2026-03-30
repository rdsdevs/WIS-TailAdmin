<?php

declare(strict_types=1);

namespace App\Models\RH;

use App\Models\Concerns\HasUuidPrimaryKey;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Collaborator extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'institution_id',
        'document_type_id',
        'document_number',
        'document_issued_at',
        'first_name',
        'second_name',
        'first_surname',
        'second_surname',
        'birth_date',
        'gender',
        'is_company',
        'company_name',
        'legal_representative',
        'email',
        'phone',
        'address',
        'type',
        'status_id',
    ];

    protected $casts = [
        'document_issued_at' => 'date',
        'birth_date' => 'date',
        'is_company' => 'boolean',
    ];

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        if ($this->is_company) {
            return $this->company_name ?? '';
        }

        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->second_name,
            $this->first_surname,
            $this->second_surname,
        ])));
    }

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(CollaboratorStatus::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(Contract::class)->where('status', 'Vigente')->latestOfMany('start_date');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeEmpleados(Builder $query): Builder
    {
        return $query->where('type', 'Empleado');
    }

    public function scopeContratistas(Builder $query): Builder
    {
        return $query->where('type', 'Contratista');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $q) => $q->where('name', 'Activo'));
    }

    public function scopeByInstitution(Builder $query, string $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }
}
