<?php

declare(strict_types=1);

namespace App\Models\RH;

use App\Models\Concerns\HasUuidPrimaryKey;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Contractor extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'institution_id',
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Nombre completo del contratista.
     * Si tiene nombre de empresa, retorna ese; de lo contrario, nombres + apellidos.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->company_name) {
            return $this->company_name;
        }

        return "{$this->first_name} {$this->last_name}";
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contractable_id')
            ->where('contractable_type', self::class);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByInstitution(\Illuminate\Database\Eloquent\Builder $query, string $institutionId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('institution_id', $institutionId);
    }
}
