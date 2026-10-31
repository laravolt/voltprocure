<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseRequestLine extends Model
{
    use HasUlids;

    protected $fillable = ['purchase_request_id', 'item_id', 'quantity', 'approved_quantity', 'estimated_unit_cost'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'approved_quantity' => 'integer',
        'estimated_unit_cost' => 'integer',
    ];

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
