<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property CategoryType $type
 * @property string $color A palette name, or a hand-picked `#rrggbb`.
 */
class Category extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'color',
    ];

    protected $casts = [
        'type' => CategoryType::class,
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    /**
     * The composite foreign key is NO ACTION, so the link has to be cleared here.
     * Losing a label must never take the transaction with it.
     */
    protected static function booted(): void
    {
        static::deleting(function (Category $model): void {
            Transaction::query()->where('category_id', $model->id)->update(['category_id' => null]);
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
