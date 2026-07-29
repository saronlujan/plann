<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * The composite foreign key is NO ACTION, so the link has to be cleared here.
     * Losing a contact must never take their transactions with it.
     */
    protected static function booted(): void
    {
        static::deleting(function (Contact $model): void {
            Transaction::query()->where('contact_id', $model->id)->update(['contact_id' => null]);
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
