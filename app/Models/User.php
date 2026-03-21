<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuidPrimaryKey;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    /**
     * Campos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'institution_id',
        'document_type',
        'document_number',
        'document_issued_at',
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    /**
     * Campos ocultos en la serialización.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'document_issued_at',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_issued_at' => 'date',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Retorna el nombre completo del usuario.
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Relación con la institución.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
