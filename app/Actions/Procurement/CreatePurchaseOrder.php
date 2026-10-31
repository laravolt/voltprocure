<?php

declare(strict_types=1);

namespace App\Actions\Procurement;

use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DocumentNumber;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Turns the approved lines of a purchase request into an issued purchase order.
 */
final readonly class CreatePurchaseOrder
{
    public function __construct(private DocumentNumber $numbers) {}

    /**
     * @param  array<string, int>  $unitCosts  purchase request line id => agreed unit cost; defaults to the estimate
     */
    public function handle(PurchaseRequest $request, Supplier $supplier, User $buyer, ?string $expectedAt = null, array $unitCosts = []): PurchaseOrder
    {
        Gate::forUser($buyer)->authorize(Permission::PURCHASE_ORDER_MANAGE);

        if ($request->status !== PurchaseRequestStatus::Approved) {
            throw ValidationException::withMessages(['status' => 'Hanya permintaan yang sudah disetujui yang dapat dipesan.']);
        }

        return DB::transaction(function () use ($request, $supplier, $buyer, $expectedAt, $unitCosts): PurchaseOrder {
            $order = PurchaseOrder::query()->create([
                'number' => $this->numbers->next(PurchaseOrder::class, 'PO'),
                'purchase_request_id' => $request->getKey(),
                'supplier_id' => $supplier->getKey(),
                'buyer_id' => $buyer->getKey(),
                'status' => PurchaseOrderStatus::Issued,
                'expected_at' => $expectedAt,
                'issued_at' => Date::now(),
            ]);

            $total = 0;

            foreach ($request->lines()->where('approved_quantity', '>', 0)->get() as $line) {
                $unitCost = $unitCosts[$line->id] ?? $line->estimated_unit_cost;
                $total += $unitCost * (int) $line->approved_quantity;

                $order->lines()->create([
                    'item_id' => $line->item_id,
                    'quantity_ordered' => $line->approved_quantity,
                    'unit_cost' => $unitCost,
                ]);
            }

            $order->update(['total' => $total]);
            $request->update(['status' => PurchaseRequestStatus::Ordered]);

            return $order;
        });
    }
}
