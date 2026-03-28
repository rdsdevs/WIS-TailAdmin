<?php

declare(strict_types=1);

namespace App\Models\RH;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class PayrollContractDetail extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'contract_id',
        'base_salary',
        'transport_allowance',
        'non_statutory_bonuses',
        'sena_rate',
        'icbf_rate',
        'compensation_fund_rate',
        'health_check_verified_at',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'non_statutory_bonuses' => 'decimal:2',
        'sena_rate' => 'decimal:2',
        'icbf_rate' => 'decimal:2',
        'compensation_fund_rate' => 'decimal:2',
        'health_check_verified_at' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
