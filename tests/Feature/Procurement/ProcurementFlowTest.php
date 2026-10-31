<?php

declare(strict_types=1);

use App\Actions\Procurement\CreatePurchaseOrder;
use App\Actions\Procurement\CreatePurchaseRequest;
use App\Actions\Procurement\DecidePurchaseRequest;
use App\Actions\Procurement\ReceiveGoods;
use App\Actions\Procurement\SubmitPurchaseRequest;
use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\StockMovementType;
use App\Models\Department;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->manager = procurementUser([Permission::PURCHASE_REQUEST_APPROVE]);
    $this->department = Department::factory()->create(['code' => 'TI', 'manager_id' => $this->manager->getKey()]);
    $this->requester = procurementUser([Permission::PURCHASE_REQUEST_CREATE], ['department_id' => $this->department->getKey()]);
    $this->buyer = procurementUser([Permission::PURCHASE_ORDER_MANAGE]);
    $this->warehouse = procurementUser([Permission::GOODS_RECEIPT_CREATE]);
    $this->toner = Item::factory()->create(['name' => 'Toner HP 26A', 'standard_cost' => 850_000]);
    $this->keyboard = Item::factory()->create(['name' => 'Keyboard USB', 'standard_cost' => 175_000]);
    $this->supplier = Supplier::factory()->create();
});

function draftRequest(): PurchaseRequest
{
    return resolve(CreatePurchaseRequest::class)->handle(test()->requester, [
        'purpose' => 'Ganti toner dan keyboard ruang server',
        'lines' => [
            ['item_id' => test()->toner->getKey(), 'quantity' => 10],
            ['item_id' => test()->keyboard->getKey(), 'quantity' => 5],
        ],
    ]);
}

it('runs a request through approval, ordering and partial receipt into stock', function (): void {
    $request = draftRequest();

    expect($request->number)->toBe('PR-'.now()->format('Y').'-0001')
        ->and($request->status)->toBe(PurchaseRequestStatus::Draft)
        ->and($request->lines)->toHaveCount(2);

    resolve(SubmitPurchaseRequest::class)->handle($request, $this->requester);
    expect($request->fresh()->status)->toBe(PurchaseRequestStatus::Submitted);

    $tonerLine = $request->lines->firstWhere('item_id', $this->toner->getKey());
    resolve(DecidePurchaseRequest::class)->approve($request->fresh(), $this->manager, [$tonerLine->getKey() => 6], 'Toner cukup 6 dulu');

    $request->refresh();
    expect($request->status)->toBe(PurchaseRequestStatus::Approved)
        ->and($request->lines->firstWhere('item_id', $this->toner->getKey())->approved_quantity)->toBe(6)
        ->and($request->estimatedTotal())->toBe(6 * 850_000 + 5 * 175_000);

    $order = resolve(CreatePurchaseOrder::class)->handle($request, $this->supplier, $this->buyer);

    expect($order->number)->toBe('PO-'.now()->format('Y').'-0001')
        ->and($order->status)->toBe(PurchaseOrderStatus::Issued)
        ->and($order->total)->toBe(6 * 850_000 + 5 * 175_000)
        ->and($request->fresh()->status)->toBe(PurchaseRequestStatus::Ordered);

    $tonerOrderLine = $order->lines->firstWhere('item_id', $this->toner->getKey());
    $receipt = resolve(ReceiveGoods::class)->handle($order, $this->warehouse, [$tonerOrderLine->getKey() => 4]);

    expect($receipt->number)->toBe('GRN-'.now()->format('Y').'-0001')
        ->and($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($this->toner->fresh()->stock_on_hand)->toBe(4)
        ->and($this->toner->stockMovements()->sole()->type)->toBe(StockMovementType::Receipt);

    $order->refresh();
    resolve(ReceiveGoods::class)->handle($order, $this->warehouse, $order->lines->mapWithKeys(
        fn ($line): array => [$line->getKey() => $line->outstandingQuantity()],
    )->all());

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received)
        ->and($this->toner->fresh()->stock_on_hand)->toBe(6)
        ->and($this->keyboard->fresh()->stock_on_hand)->toBe(5);
});

it('lets only the department manager decide', function (): void {
    $request = draftRequest();
    resolve(SubmitPurchaseRequest::class)->handle($request, $this->requester);

    $otherManager = procurementUser([Permission::PURCHASE_REQUEST_APPROVE]);

    resolve(DecidePurchaseRequest::class)->approve($request->fresh(), $otherManager);
})->throws(AuthorizationException::class);

it('requires a reason and records the rejection', function (): void {
    $request = draftRequest();
    resolve(SubmitPurchaseRequest::class)->handle($request, $this->requester);

    resolve(DecidePurchaseRequest::class)->reject($request->fresh(), $this->manager, 'Anggaran kuartal ini habis');

    expect($request->fresh())
        ->status->toBe(PurchaseRequestStatus::Rejected)
        ->decision_note->toBe('Anggaran kuartal ini habis')
        ->decided_by->toBe($this->manager->getKey());
});

it('refuses to receive more than is still outstanding', function (): void {
    $request = draftRequest();
    resolve(SubmitPurchaseRequest::class)->handle($request, $this->requester);
    resolve(DecidePurchaseRequest::class)->approve($request->fresh(), $this->manager);
    $order = resolve(CreatePurchaseOrder::class)->handle($request->fresh(), $this->supplier, $this->buyer);

    $line = $order->lines->firstWhere('item_id', $this->toner->getKey());

    expect(fn () => resolve(ReceiveGoods::class)->handle($order, $this->warehouse, [$line->getKey() => 11]))
        ->toThrow(ValidationException::class);
    expect($this->toner->fresh()->stock_on_hand)->toBe(0);
});

it('does not order a request that is not approved', function (): void {
    resolve(CreatePurchaseOrder::class)->handle(draftRequest(), $this->supplier, $this->buyer);
})->throws(ValidationException::class);

it('numbers documents sequentially per year', function (): void {
    $first = draftRequest();
    $second = draftRequest();

    expect([$first->number, $second->number])->toBe([
        'PR-'.now()->format('Y').'-0001',
        'PR-'.now()->format('Y').'-0002',
    ]);
});
