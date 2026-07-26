<?php

namespace App\Models;

use App\Enums\LabelColor;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property LabelColor $color
 */
class Tag extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
    ];

    protected $casts = [
        'color' => LabelColor::class,
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<Transaction, $this>
     */
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class);
    }
}
