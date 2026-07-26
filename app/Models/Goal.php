<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $currency_id
 * @property string $name
 * @property string $target_amount
 * @property string $current_amount
 * @property Carbon|null $target_date
 */
class Goal extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'currency_id',
        'name',
        'target_amount',
        'current_amount',
        'target_date',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'target_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
