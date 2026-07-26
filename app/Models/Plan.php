<?php

namespace App\Models;

use App\Enums\PlanSlug;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property PlanSlug $slug
 * @property string $name
 * @property string|null $description
 * @property int $monthly_price_cents
 * @property int $max_users
 * @property string|null $stripe_price_id
 * @property int $sort_order
 * @property bool $is_active
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'monthly_price_cents',
        'max_users',
        'stripe_price_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'slug' => PlanSlug::class,
        'monthly_price_cents' => 'integer',
        'max_users' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<Plan>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Total amount charged once per year, in cents.
     */
    public function annualPriceCents(): int
    {
        return $this->monthly_price_cents * 12;
    }
}
