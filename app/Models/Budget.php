<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $category_id
 * @property int $currency_id
 * @property string $amount
 */
class Budget extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'currency_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
