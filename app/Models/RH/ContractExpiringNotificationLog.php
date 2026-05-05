<?php

declare(strict_types=1);

namespace App\Models\RH;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractExpiringNotificationLog extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'contract_expiring_notifications_log';

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'threshold_days',
        'notified_at',
    ];

    protected $casts = [
        'threshold_days' => 'integer',
        'notified_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
