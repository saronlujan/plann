<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One part of what a transaction was made of, and what that part was worth.
 *
 * Modelled in its own right rather than as a plain pivot because a line outlives
 * the service it named: retiring a service leaves the line standing with its
 * amount, and only a relation that does not go through `services` can still see
 * it. Without that, editing the transaction would silently drop the money.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $transaction_id
 * @property int|null $service_id
 * @property string $amount
 */
class TransactionLine extends Model
{
    use BelongsToTenant;

    protected $table = 'service_transaction';

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'service_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Null once the service has been retired.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
