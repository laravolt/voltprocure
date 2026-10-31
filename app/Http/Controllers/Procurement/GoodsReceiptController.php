<?php

declare(strict_types=1);

namespace App\Http\Controllers\Procurement;

use App\Actions\Procurement\ReceiveGoods;
use App\Http\Support\QuantityInput;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class GoodsReceiptController
{
    public function __invoke(Request $request, PurchaseOrder $purchaseOrder, ReceiveGoods $receive): RedirectResponse
    {
        $request->validate([
            'received' => ['required', 'array'],
            'received.*' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $quantities = array_filter(QuantityInput::from($request, 'received'), fn (int $quantity): bool => $quantity > 0);

        $receipt = $receive->handle(
            $purchaseOrder,
            $user,
            $quantities,
            $request->filled('note') ? $request->string('note')->trim()->toString() : null,
        );

        return to_route('purchase-orders.show', $purchaseOrder)
            ->with('success', "{$receipt->number} dicatat. Stok sudah bertambah.");
    }
}
