<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['sku', 'name', 'category', 'unit', 'reorder_point', 'standard_cost', 'stock_on_hand', 'is_active'];

    /**
     * @var array<string, string>
     */
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
