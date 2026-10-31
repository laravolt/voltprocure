<?php

declare(strict_types=1);

namespace App\Http\Controllers\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

final class SupplierController
{
    public function __invoke(): View
    {
        $suppliers = Supplier::query()
            ->withCount(['purchaseOrders as open_orders_count' => fn (Builder $query): Builder => $query->whereIn('status', [PurchaseOrderStatus::Issued, PurchaseOrderStatus::PartiallyReceived])])
            ->withSum('purchaseOrders as ordered_total', 'total')
            ->orderBy('name')
            ->get();

        return view('procurement.suppliers.index', ['suppliers' => $suppliers]);
    }
}
