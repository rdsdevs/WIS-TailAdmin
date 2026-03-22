<?php

declare(strict_types=1);

namespace App\Models\RH;

use App\Models\Concerns\HasUuidPrimaryKey;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class CommittedValue extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'institution_id',
        'contract_id',
        'accounting_account',
        'cost_center',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
