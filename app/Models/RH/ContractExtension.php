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

class ContractExtension extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /** @var array<int, string> */
    const TYPES = ['tiempo', 'valor', 'tiempo_y_valor'];

    protected $fillable = [
        'contract_id',
        'extension_date',
        'reason',
        'extension_type',
        'extension_months',
        'extension_days',
        'extension_value',
        'new_end_date',
        'approval_date',
        'institution_id',
        'committed_value_id',
    ];

    protected $casts = [
        'extension_date' => 'date',
        'extension_value' => 'decimal:2',
        'approval_date' => 'date',
        'new_end_date' => 'date',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function committedValue(): BelongsTo
    {
        return $this->belongsTo(CommittedValue::class);
    }
}
