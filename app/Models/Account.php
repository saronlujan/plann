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
 * @property string $balance
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
        'balance',
        'credit_limit',
        'closing_day',
        'due_day',
    ];

    protected $casts = [
        'kind' => AccountKind::class,
        'balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'closing_day' => 'integer',
        'due_day' => 'integer',
    ];

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
