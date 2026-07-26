<?php

namespace App\Models;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
