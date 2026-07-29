<?php

namespace App\Models;

use App\Enums\AccountKind;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $currency_id
 * @property string $name
 * @property AccountKind $kind
 * @property string|null $credit_limit
 * @property int|null $closing_day
 * @property int|null $due_day
 */
class Account extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'currency_id',
        'name',
        'kind',
        'credit_limit',
        'closing_day',
        'due_day',
    ];

    protected $casts = [
        'kind' => AccountKind::class,
        'credit_limit' => 'decimal:2',
        'closing_day' => 'integer',
        'due_day' => 'integer',
    ];

    /**
     * The composite foreign key is NO ACTION, so the link has to be cleared here.
     * Losing a source must never take the transaction with it.
     */
    protected static function booted(): void
    {
        static::deleting(function (Account $model): void {
            Transaction::query()->where('account_id', $model->id)->update(['account_id' => null]);
        });
    }

    public function isCreditCard(): bool
    {
        return $this->kind === AccountKind::CreditCard;
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
