<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseOrderLine extends Model
{
    use HasUlids;

    protected $fillable = ['purchase_order_id', 'item_id', 'quantity_ordered', 'quantity_received', 'unit_cost'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_cost' => 'integer',
    ];

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }
}
