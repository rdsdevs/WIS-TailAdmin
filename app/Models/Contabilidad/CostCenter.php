<?php

declare(strict_types=1);

namespace App\Models\Contabilidad;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class CostCenter extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'cost_centers';

    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(
            fn (Builder $q) => $q
                ->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
        );
    }
}
