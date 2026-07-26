<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property ContactType $type
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $document
 * @property string|null $notes
 */
class Contact extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'email',
        'phone',
        'document',
        'notes',
    ];

    protected $casts = [
        'type' => ContactType::class,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
