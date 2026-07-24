<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'account_id',
        'currency_id',
        'movement_type',
        'type',
        'installment_frequency',
        'installments_total',
        'installment_number',
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
        'effective_date' => 'date',
        'paid_at' => 'date',
        'effective_until' => 'date',
        'adjustment_month' => 'date',
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
}
