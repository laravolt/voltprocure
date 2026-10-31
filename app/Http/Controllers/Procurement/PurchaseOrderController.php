<?php

declare(strict_types=1);

namespace App\Http\Controllers\Procurement;

use App\Actions\Procurement\CreatePurchaseOrder;
use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Http\Support\QuantityInput;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PurchaseOrderController
{
    public function index(Request $request): View
    {
        $this->authorizeViewing();

        $filter = $request->string('status')->toString();
        $status = PurchaseOrderStatus::tryFrom($filter);

        $orders = PurchaseOrder::query()
            ->when($filter === 'open', fn (Builder $query): Builder => $query->whereIn('status', [PurchaseOrderStatus::Issued, PurchaseOrderStatus::PartiallyReceived]))
            ->when($status, fn (Builder $query, PurchaseOrderStatus $status): Builder => $query->where('status', $status))
            ->with(['supplier', 'purchaseRequest', 'lines'])
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('procurement.purchase-orders.index', [
            'orders' => $orders,
            'status' => $filter === 'open' || $status ? $filter : '',
        ]);
    }

    public function create(Request $request): View
    {
        $purchaseRequest = PurchaseRequest::query()
            ->whereKey($request->string('request')->toString())
            ->where('status', PurchaseRequestStatus::Approved)
            ->with(['lines' => fn (HasMany $query): HasMany => $query->where('approved_quantity', '>', 0), 'lines.item', 'department', 'requester'])
            ->firstOrFail();

        return view('procurement.purchase-orders.create', [
            'pr' => $purchaseRequest,
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CreatePurchaseOrder $create): RedirectResponse
    {
        $request->validate([
            'purchase_request_id' => ['required', 'exists:purchase_requests,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'expected_at' => ['required', 'date', 'after_or_equal:today'],
            'unit_cost' => ['array'],
            'unit_cost.*' => ['nullable', 'integer', 'min:0'],
        ], [], [
            'supplier_id' => 'pemasok',
            'expected_at' => 'perkiraan tiba',
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $order = $create->handle(
            PurchaseRequest::query()->findOrFail($request->string('purchase_request_id')->toString()),
            Supplier::query()->findOrFail($request->string('supplier_id')->toString()),
            $user,
            $request->string('expected_at')->toString(),
            QuantityInput::from($request, 'unit_cost'),
        );

        return to_route('purchase-orders.show', $order)->with('success', "{$order->number} terbit dan siap dikirim ke pemasok.");
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizeViewing();

        $purchaseOrder->load(['supplier', 'buyer', 'purchaseRequest.department', 'lines.item', 'goodsReceipts.receiver', 'goodsReceipts.lines.purchaseOrderLine.item']);

        return view('procurement.purchase-orders.show', [
            'po' => $purchaseOrder,
            'canReceive' => $purchaseOrder->status->isOpen() && Gate::allows(Permission::GOODS_RECEIPT_CREATE),
        ]);
    }

    private function authorizeViewing(): void
    {
        abort_unless(Gate::any([Permission::PURCHASE_ORDER_MANAGE, Permission::GOODS_RECEIPT_CREATE]), 403);
    }
}
