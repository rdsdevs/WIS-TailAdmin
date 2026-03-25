<?php

declare(strict_types=1);

namespace App\Models\Certificados;

use App\Models\Concerns\HasUuidPrimaryKey;
use App\Models\Institution;
use App\Models\RH\CertificateSignature;
use App\Models\RH\Collaborator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Certificate extends Model implements Auditable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'institution_id',
        'collaborator_id',
        'certificate_signature_id',
        'issued_by',
        'verification_code',
        'certificate_type',
        'addressed_to',
        'issued_at',
        'collaborator_snapshot',
        'contracts_snapshot',
        'options_snapshot',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'collaborator_snapshot' => 'array',
        'contracts_snapshot'    => 'array',
        'options_snapshot'      => 'array',
        'issued_at'             => 'datetime',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(CertificateSignature::class, 'certificate_signature_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getAddressedToLabel(): string
    {
        return $this->addressed_to ?? 'a quien interese';
    }

    public function isEmpleado(): bool
    {
        return $this->certificate_type === 'empleado';
    }

    public function isContratista(): bool
    {
        return $this->certificate_type === 'contratista';
    }
}
