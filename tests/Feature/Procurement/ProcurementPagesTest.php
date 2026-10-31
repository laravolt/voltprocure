<?php

declare(strict_types=1);

use App\Actions\Procurement\CreatePurchaseOrder;
use App\Actions\Procurement\CreatePurchaseRequest;
use App\Actions\Procurement\DecidePurchaseRequest;
use App\Actions\Procurement\SubmitPurchaseRequest;
use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\Department;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;

beforeEach(function (): void {
    $this->withoutVite();

    $this->manager = procurementUser([Permission::PURCHASE_REQUEST_APPROVE], ['name' => 'Bagus Santoso']);
    $this->department = Department::factory()->create(['code' => 'TI', 'manager_id' => $this->manager->getKey()]);
    $this->requester = procurementUser([Permission::PURCHASE_REQUEST_CREATE], ['department_id' => $this->department->getKey()]);
    $this->buyer = procurementUser([Permission::PURCHASE_ORDER_MANAGE, Permission::MASTER_DATA_MANAGE], ['name' => 'Andi Pratama']);
    $this->warehouse = procurementUser([Permission::GOODS_RECEIPT_CREATE]);
    $this->toner = Item::factory()->create(['sku' => 'ITM-0101', 'name' => 'Toner HP 26A', 'standard_cost' => 850_000, 'stock_on_hand' => 2, 'reorder_point' => 6]);
    $this->paper = Item::factory()->create(['sku' => 'ITM-0201', 'name' => 'Kertas A4 80 g', 'standard_cost' => 54_000, 'stock_on_hand' => 40, 'reorder_point' => 10]);
    $this->supplier = Supplier::factory()->create(['name' => 'PT Sinar Data Komputindo']);
});

function submittedRequest(int $toner = 4): PurchaseRequest
{
    $request = resolve(CreatePurchaseRequest::class)->handle(test()->requester, [
        'purpose' => 'Toner printer lantai 3 habis',
        'lines' => [['item_id' => test()->toner->getKey(), 'quantity' => $toner]],
    ]);
    resolve(SubmitPurchaseRequest::class)->handle($request, test()->requester);

    return $request->refresh();
}

function issuedOrder(): PurchaseOrder
{
    $request = submittedRequest();
    resolve(DecidePurchaseRequest::class)->approve($request, test()->manager);

    return resolve(CreatePurchaseOrder::class)->handle($request->refresh(), test()->supplier, test()->buyer, now()->addWeek()->toDateString());
}

it('greets the manager with the requests waiting for their decision', function (): void {
    $request = submittedRequest();

    $this->actingAs($this->manager)->get(route('home'))
        ->assertOk()
        ->assertSee('Selamat')
        ->assertSee('Bagus')
        ->assertSee($request->number)
        ->assertSee('Putuskan')
        ->assertSee('Toner HP 26A');
});

it('shows procurement the approved requests that still need an order', function (): void {
    $request = submittedRequest();
    resolve(DecidePurchaseRequest::class)->approve($request, $this->manager);

    $this->actingAs($this->buyer)->get(route('home'))
        ->assertOk()
        ->assertSee('Buat PO')
        ->assertSee(route('purchase-orders.create', ['request' => $request->getKey()]), false);
});

it('tells a person with nothing to do that their desk is clear', function (): void {
    $this->actingAs($this->requester)->get(route('home'))
        ->assertOk()
        ->assertSee('Meja Anda rapi');
});

it('lists only the requests a person is allowed to see', function (): void {
    $own = submittedRequest();
    $otherDepartment = Department::factory()->create(['code' => 'KEU']);
    $colleague = procurementUser([Permission::PURCHASE_REQUEST_CREATE], ['department_id' => $otherDepartment->getKey()]);
    $foreign = resolve(CreatePurchaseRequest::class)->handle($colleague, [
        'purpose' => 'Kertas arsip pajak',
        'lines' => [['item_id' => $this->paper->getKey(), 'quantity' => 10]],
    ]);

    $this->actingAs($this->requester)->get(route('purchase-requests.index'))
        ->assertOk()
        ->assertSee($own->number)
        ->assertDontSee($foreign->number);

    $this->actingAs($this->requester)->get(route('purchase-requests.show', $foreign))->assertNotFound();

    $this->actingAs($this->buyer)->get(route('purchase-requests.index', ['status' => 'draft']))
        ->assertOk()
        ->assertSee($foreign->number)
        ->assertDontSee($own->number);
});

it('creates and submits a request from the form, ignoring blank rows', function (): void {
    $this->actingAs($this->requester)->get(route('purchase-requests.create'))->assertOk()->assertSee('Toner HP 26A');

    $response = $this->actingAs($this->requester)->post(route('purchase-requests.store'), [
        'purpose' => 'Toner printer lantai 3 habis',
        'needed_by' => now()->addDays(5)->toDateString(),
        'intent' => 'submit',
        'lines' => [
            ['item_id' => $this->toner->getKey(), 'quantity' => '3'],
            ['item_id' => '', 'quantity' => ''],
        ],
    ]);

    $request = PurchaseRequest::query()->sole();
    $response->assertRedirect(route('purchase-requests.show', $request))->assertSessionHas('success');

    expect($request->status)->toBe(PurchaseRequestStatus::Submitted)
        ->and($request->lines)->toHaveCount(1)
        ->and($request->lines->first()->quantity)->toBe(3);
});

it('keeps a request as a draft and lets the requester submit it later', function (): void {
    $this->actingAs($this->requester)->post(route('purchase-requests.store'), [
        'purpose' => 'Kertas A4 bulan November',
        'intent' => 'draft',
        'lines' => [['item_id' => $this->paper->getKey(), 'quantity' => '25']],
    ])->assertSessionHasNoErrors();

    $request = PurchaseRequest::query()->sole();
    expect($request->status)->toBe(PurchaseRequestStatus::Draft);

    $this->actingAs($this->requester)->get(route('purchase-requests.show', $request))->assertOk()->assertSee('Ajukan ke manajer');
    $this->actingAs($this->requester)->post(route('purchase-requests.submit', $request))->assertRedirect();

    expect($request->fresh()->status)->toBe(PurchaseRequestStatus::Submitted);
});

it('rejects a request form without any item or with a half-filled row', function (): void {
    $this->actingAs($this->requester)->post(route('purchase-requests.store'), [
        'purpose' => 'Kosong',
        'lines' => [['item_id' => '', 'quantity' => '']],
    ])->assertSessionHasErrors('lines');

    $this->actingAs($this->requester)->post(route('purchase-requests.store'), [
        'purpose' => 'Setengah',
        'lines' => [['item_id' => $this->toner->getKey(), 'quantity' => '']],
    ])->assertSessionHasErrors('lines.0.quantity');

    expect(PurchaseRequest::query()->count())->toBe(0);
});

it('lets the manager approve fewer items than requested', function (): void {
    $request = submittedRequest(toner: 6);
    $line = $request->lines->sole();

    $this->actingAs($this->manager)->get(route('purchase-requests.show', $request))->assertOk()->assertSee('Setujui');

    $this->actingAs($this->manager)->post(route('purchase-requests.decision', $request), [
        'decision' => 'approve',
        'approved' => [$line->getKey() => '4'],
        'note' => 'Cukup 4 dulu',
    ])->assertSessionHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(PurchaseRequestStatus::Approved)
        ->and($request->lines->sole()->approved_quantity)->toBe(4)
        ->and($request->decision_note)->toBe('Cukup 4 dulu');
});

it('requires a reason to reject', function (): void {
    $request = submittedRequest();

    $this->actingAs($this->manager)->post(route('purchase-requests.decision', $request), ['decision' => 'reject'])
        ->assertSessionHasErrors('note');

    $this->actingAs($this->manager)->post(route('purchase-requests.decision', $request), ['decision' => 'reject', 'note' => 'Belum masuk anggaran'])
        ->assertSessionHasNoErrors();

    expect($request->fresh()->status)->toBe(PurchaseRequestStatus::Rejected);
});

it('does not let a requester decide on a request', function (): void {
    $request = submittedRequest();

    $this->actingAs($this->requester)->post(route('purchase-requests.decision', $request), ['decision' => 'approve'])->assertForbidden();
});

it('issues a purchase order at the agreed price', function (): void {
    $request = submittedRequest();
    resolve(DecidePurchaseRequest::class)->approve($request, $this->manager);
    $line = $request->lines->sole();

    $this->actingAs($this->buyer)->get(route('purchase-orders.create', ['request' => $request->getKey()]))
        ->assertOk()
        ->assertSee($request->number)
        ->assertSee('PT Sinar Data Komputindo');

    $this->actingAs($this->buyer)->post(route('purchase-orders.store'), [
        'purchase_request_id' => $request->getKey(),
        'supplier_id' => $this->supplier->getKey(),
        'expected_at' => now()->addWeek()->toDateString(),
        'unit_cost' => [$line->getKey() => '820000'],
    ])->assertSessionHasNoErrors();

    $order = PurchaseOrder::query()->sole();
    expect($order->total)->toBe(4 * 820_000)
        ->and($request->fresh()->status)->toBe(PurchaseRequestStatus::Ordered);

    $this->actingAs($this->buyer)->get(route('purchase-orders.show', $order))->assertOk()->assertSee($order->number);
    $this->actingAs($this->requester)->get(route('purchase-orders.index'))->assertForbidden();
    $this->actingAs($this->requester)->get(route('purchase-orders.create', ['request' => $request->getKey()]))->assertForbidden();
});

it('records a partial receipt from the order page and adds it to stock', function (): void {
    $order = issuedOrder();
    $line = $order->lines->sole();

    $this->actingAs($this->warehouse)->get(route('purchase-orders.show', $order))->assertOk()->assertSee('Catat penerimaan');

    $this->actingAs($this->warehouse)->post(route('purchase-orders.receipts.store', $order), [
        'received' => [$line->getKey() => '3'],
        'note' => 'SJ-7781',
    ])->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived)
        ->and($this->toner->fresh()->stock_on_hand)->toBe(2 + 3);

    $this->actingAs($this->warehouse)->get(route('items.show', $this->toner))->assertOk()->assertSee('Penerimaan')->assertSee('+3');

    $this->actingAs($this->buyer)->post(route('purchase-orders.receipts.store', $order), ['received' => [$line->getKey() => '1']])->assertForbidden();
});

it('filters stock down to items at or below their reorder point', function (): void {
    $this->actingAs($this->requester)->get(route('items.index', ['stock' => 'low']))
        ->assertOk()
        ->assertSee('Toner HP 26A')
        ->assertDontSee('Kertas A4 80 g');

    $this->actingAs($this->requester)->get(route('items.index', ['q' => 'kertas']))
        ->assertOk()
        ->assertSee('Kertas A4 80 g')
        ->assertDontSee('Toner HP 26A');
});

it('shows suppliers to procurement only', function (): void {
    $this->actingAs($this->buyer)->get(route('suppliers.index'))->assertOk()->assertSee('PT Sinar Data Komputindo');
    $this->actingAs($this->requester)->get(route('suppliers.index'))->assertForbidden();
});
