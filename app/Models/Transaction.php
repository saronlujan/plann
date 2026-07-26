<?php

namespace App\Models;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $account_id
 * @property int $currency_id
 * @property int|null $category_id
 * @property TransactionMovementType|null $movement_type
 * @property bool $is_transfer
 * @property TransactionType $type
 * @property TransactionInstallmentFrequency|null $installment_frequency
 * @property int|null $installments_total
 * @property int|null $installment_number
 * @property string|null $interest_amount
 * @property string|null $attachment_path
 * @property string|null $series_uuid
 * @property CarbonImmutable $effective_date
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $effective_until
 * @property CarbonImmutable|null $adjustment_month
 * @property string $amount
 * @property string $adjustment_amount
 * @property string $description
 */
class Transaction extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'account_id',
        'currency_id',
        'category_id',
        'movement_type',
        'is_transfer',
        'type',
        'installment_frequency',
        'installments_total',
        'installment_number',
        'interest_amount',
        'attachment_path',
        'series_uuid',
        'effective_date',
        'paid_at',
        'effective_until',
        'adjustment_month',
        'amount',
        'adjustment_amount',
        'description',
    ];

    protected $casts = [
        'movement_type' => TransactionMovementType::class,
        'is_transfer' => 'boolean',
        'type' => TransactionType::class,
        'installment_frequency' => TransactionInstallmentFrequency::class,
        'effective_date' => 'date',
        'paid_at' => 'date',
        'effective_until' => 'date',
        'adjustment_month' => 'date',
        'interest_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
