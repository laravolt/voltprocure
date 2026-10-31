<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\StockMovement;
use App\Models\Supplier;

it('persists every procurement model from its factory', function (string $model): void {
    $record = $model::factory()->create();

    expect($record->exists)->toBeTrue()
        ->and($model::query()->whereKey($record->getKey())->exists())->toBeTrue();
})->with([
    Department::class,
    Item::class,
    Supplier::class,
    PurchaseRequest::class,
    PurchaseRequestLine::class,
    PurchaseOrder::class,
    PurchaseOrderLine::class,
    GoodsReceipt::class,
    GoodsReceiptLine::class,
    StockMovement::class,
]);

it('flags items at or below the reorder point', function (): void {
    expect(Item::factory()->make(['stock_on_hand' => 5, 'reorder_point' => 5])->isBelowReorderPoint())->toBeTrue()
        ->and(Item::factory()->make(['stock_on_hand' => 6, 'reorder_point' => 5])->isBelowReorderPoint())->toBeFalse();
});
