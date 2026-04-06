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
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Position extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'email',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(PositionEmail::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function functions(): HasMany
    {
        return $this->hasMany(PositionFunction::class);
    }

    public function positionChangeHistory(): HasMany
    {
        return $this->hasMany(PositionChangeHistory::class, 'new_position_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByInstitution(Builder $query, string $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }
}
