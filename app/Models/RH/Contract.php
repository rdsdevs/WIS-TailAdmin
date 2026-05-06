<?php

declare(strict_types=1);

namespace App\Models\RH;

use App\Models\Concerns\HasUuidPrimaryKey;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Contract extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'institution_id',
        'collaborator_id',
        'contract_type_id',
        'position_id',
        'contract_number',
        'contract_code',
        'start_date',
        'end_date',
        'object',
        'obligations',
        'salary',
        'fees',
        'position_email',
        'status',
        'early_termination_date',
        'early_termination_reason',
        'early_terminated_by',
        'early_terminated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'salary' => 'decimal:2',
        'fees' => 'decimal:2',
        'early_termination_date' => 'date',
        'early_terminated_at' => 'datetime',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(ContractExtension::class);
    }

    public function committedValues(): HasMany
    {
        return $this->hasMany(CommittedValue::class);
    }

    public function payrollDetail(): HasOne
    {
        return $this->hasOne(PayrollContractDetail::class);
    }

    public function positionChangeHistory(): HasMany
    {
        return $this->hasMany(PositionChangeHistory::class);
    }

    public function earlyTerminatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'early_terminated_by');
    }

    // ── Accessors / Helpers de dominio ───────────────────────────────────────

    public function isEarlyTerminated(): bool
    {
        return $this->early_termination_date !== null;
    }

    /** Retorna true si el contrato pertenece a un año anterior al actual. */
    public function isFromPreviousYear(): bool
    {
        return $this->start_date !== null && $this->start_date->year < now()->year;
    }

    /** Retorna true si el contrato pertenece al año actual. */
    public function isCurrentYear(): bool
    {
        return $this->start_date !== null && $this->start_date->year === now()->year;
    }

    /**
     * Retorna true si el contrato puede ser prorrogado:
     * - Vigente (cualquier año), O
     * - Terminado + año actual.
     * Retorna false si está Terminado + año anterior.
     */
    public function canBeProrrogated(): bool
    {
        if ($this->status === 'Vigente') {
            return true;
        }

        return $this->status === 'Terminado' && $this->isCurrentYear();
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Vigente');
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', 'Vigente')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays($days));
    }

    public function scopeEarlyTerminated(Builder $query): Builder
    {
        return $query->whereNotNull('early_termination_date');
    }
}
