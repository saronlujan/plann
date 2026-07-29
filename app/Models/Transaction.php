<?php

namespace App\Models;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $account_id
 * @property int $currency_id
 * @property int|null $category_id
 * @property int|null $contact_id
 * @property TransactionMovementType|null $movement_type
 * @property bool $is_transfer
 * @property TransactionType $type
 * @property TransactionInstallmentFrequency|null $installment_frequency
 * @property int|null $installments_total
 * @property int|null $installment_number
 * @property string|null $interest_amount
 * @property string|null $attachment
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
        'contact_id',
        'movement_type',
        'is_transfer',
        'type',
        'installment_frequency',
        'installments_total',
        'installment_number',
        'interest_amount',
        'attachment',
        'series_uuid',
        'is_skipped',
        'effective_date',
        'paid_at',
        'effective_until',
        'adjustment_month',
        'amount',
        'adjustment_amount',
        'description',
        'note',
        'observations',
    ];

    protected $casts = [
        'movement_type' => TransactionMovementType::class,
        'is_transfer' => 'boolean',
        'is_skipped' => 'boolean',
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
     * Who the money came from or went to: the client on an income, the provider
     * on an expense.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * What this transaction was made of, and what each part was worth. The lines
     * always add up to the amount; a transaction with no lines is simply one
     * nobody broke down.
     *
     * Deliberately not a belongsToMany over `services`: that join drops the lines
     * whose service has been retired, and those still carry real money.
     *
     * @return HasMany<TransactionLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(TransactionLine::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        // Eager loading builds the relation on a bare instance, so tenant_id may
        // not be populated yet; the request's tenant context is the fallback.
        // withPivotValue both fills tenant_id on attach/sync and constrains reads,
        // so the pivot can never bridge two workspaces.
        $relation = $this->belongsToMany(Tag::class);
        $tenantId = $this->tenant_id ?? app(TenantContext::class)->tenantId();

        return $tenantId === null ? $relation : $relation->withPivotValue('tenant_id', $tenantId);
    }
}
