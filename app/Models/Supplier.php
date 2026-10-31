<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable(['code', 'name', 'contact_name', 'email', 'phone', 'city', 'payment_terms', 'is_active'])]
final class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, HasUlids;

    /**
     * @var array<string, string>
     */
    #[Override]
    protected $casts = ['is_active' => 'boolean'];

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
