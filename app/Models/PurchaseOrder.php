<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseOrder extends Model
{
    use HasUlids;

    protected $fillable = ['number', 'purchase_request_id', 'supplier_id', 'buyer_id', 'status', 'expected_at', 'issued_at', 'total'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'expected_at' => 'date',
        'issued_at' => 'datetime',
        'total' => 'integer',
    ];

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * @return HasMany<GoodsReceipt, $this>
     */
    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }
}
