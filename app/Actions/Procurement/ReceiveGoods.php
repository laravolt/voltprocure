<?php

declare(strict_types=1);

namespace App\Actions\Procurement;

use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\DocumentNumber;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Records a (possibly partial) delivery against a purchase order and moves
 * the received quantities into stock.
 */
final readonly class ReceiveGoods
{
    public function __construct(private DocumentNumber $numbers) {}

    /**
     * @param  array<string, int>  $quantities  purchase order line id => quantity received now
     */
    public function handle(PurchaseOrder $order, User $receiver, array $quantities, ?string $note = null): GoodsReceipt
    {
        Gate::forUser($receiver)->authorize(Permission::GOODS_RECEIPT_CREATE);

        if (! $order->status->isOpen()) {
            throw ValidationException::withMessages(['status' => 'Pesanan ini tidak sedang menunggu barang.']);
        }

        $quantities = array_filter($quantities, fn (int $quantity): bool => $quantity > 0);

        if ($quantities === []) {
            throw ValidationException::withMessages(['lines' => 'Isi jumlah barang yang diterima.']);
        }

        return DB::transaction(function () use ($order, $receiver, $quantities, $note): GoodsReceipt {
            $now = Date::now();

            $receipt = $order->goodsReceipts()->create([
                'number' => $this->numbers->next(GoodsReceipt::class, 'GRN'),
                'received_by' => $receiver->getKey(),
                'received_at' => $now,
                'note' => $note,
            ]);

            foreach ($order->lines()->lockForUpdate()->get() as $line) {
                $quantity = $quantities[$line->id] ?? 0;

                if ($quantity === 0) {
                    continue;
                }

                $item = $line->item()->lockForUpdate()->firstOrFail();

                if ($quantity > $line->outstandingQuantity()) {
                    throw ValidationException::withMessages([
                        'lines' => sprintf('%s: maksimal %d %s lagi.', $item->name, $line->outstandingQuantity(), $item->unit),
                    ]);
                }

                $receipt->lines()->create(['purchase_order_line_id' => $line->getKey(), 'quantity' => $quantity]);
                $line->increment('quantity_received', $quantity);
                $item->increment('stock_on_hand', $quantity);
                $item->stockMovements()->create([
                    'quantity' => $quantity,
                    'type' => StockMovementType::Receipt,
                    'source_type' => $receipt->getMorphClass(),
                    'source_id' => $receipt->getKey(),
                    'created_by' => $receiver->getKey(),
                    'moved_at' => $now,
                ]);
            }

            $fullyReceived = $order->lines()->whereColumn('quantity_received', '<', 'quantity_ordered')->doesntExist();
            $order->update(['status' => $fullyReceived ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartiallyReceived]);

            return $receipt;
        });
    }
}
