<?php

declare(strict_types=1);

namespace App\Http\Controllers\Procurement;

use App\Actions\Procurement\SubmitPurchaseRequest;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SubmitPurchaseRequestController
{
    public function __invoke(Request $request, PurchaseRequest $purchaseRequest, SubmitPurchaseRequest $submit): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $submit->handle($purchaseRequest, $user);

        return to_route('purchase-requests.show', $purchaseRequest)
            ->with('success', "{$purchaseRequest->number} diajukan ke manajer departemen.");
    }
}
