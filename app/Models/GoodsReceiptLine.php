<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GoodsReceiptLine extends Model
{
    use HasUlids;

    protected $fillable = ['goods_receipt_id', 'purchase_order_line_id', 'quantity'];

    /**
     * @var array<string, string>
     */
    protected $casts = ['quantity' => 'integer'];

    /**
     * @return BelongsTo<PurchaseOrderLine, $this>
     */
    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }
}
