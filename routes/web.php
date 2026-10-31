<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Procurement\GoodsReceiptController;
use App\Http\Controllers\Procurement\ItemController;
use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Controllers\Procurement\PurchaseRequestController;
use App\Http\Controllers\Procurement\PurchaseRequestDecisionController;
use App\Http\Controllers\Procurement\SubmitPurchaseRequestController;
use App\Http\Controllers\Procurement\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', 'auth/login');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/home', HomeController::class)->name('home');

    // Purchase requests: visibility is scoped per user inside the controller.
    Route::get('purchase-requests', [PurchaseRequestController::class, 'index'])->name('purchase-requests.index');
    Route::get('purchase-requests/create', [PurchaseRequestController::class, 'create'])
        ->middleware('can:'.Permission::PURCHASE_REQUEST_CREATE)->name('purchase-requests.create');
    Route::post('purchase-requests', [PurchaseRequestController::class, 'store'])
        ->middleware('can:'.Permission::PURCHASE_REQUEST_CREATE)->name('purchase-requests.store');
    Route::get('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('purchase-requests.show');
    Route::post('purchase-requests/{purchaseRequest}/submit', SubmitPurchaseRequestController::class)->name('purchase-requests.submit');
    Route::post('purchase-requests/{purchaseRequest}/decision', PurchaseRequestDecisionController::class)
        ->middleware('can:'.Permission::PURCHASE_REQUEST_APPROVE)->name('purchase-requests.decision');

    Route::middleware('can:'.Permission::PURCHASE_ORDER_MANAGE)->group(function (): void {
        Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('suppliers', SupplierController::class)->name('suppliers.index');
    });

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::post('purchase-orders/{purchaseOrder}/receipts', GoodsReceiptController::class)
        ->middleware('can:'.Permission::GOODS_RECEIPT_CREATE)->name('purchase-orders.receipts.store');

    Route::get('items', [ItemController::class, 'index'])->name('items.index');
    Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');
});

include __DIR__.'/auth.php';
include __DIR__.'/my.php';
