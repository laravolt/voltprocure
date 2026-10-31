<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable(['sku', 'name', 'category', 'unit', 'reorder_point', 'standard_cost', 'stock_on_hand', 'is_active'])]
final class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, HasUlids;

    /**
     * @var array<string, string>
     */
    #[Override]
    protected $casts = [
        'reorder_point' => 'integer',
        'standard_cost' => 'integer',
        'stock_on_hand' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isBelowReorderPoint(): bool
    {
        return $this->stock_on_hand <= $this->reorder_point;
    }
}
