<?php

declare(strict_types=1);

namespace App\Http\Controllers\Procurement;

use App\Actions\Procurement\DecidePurchaseRequest;
use App\Http\Support\QuantityInput;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PurchaseRequestDecisionController
{
    public function __invoke(Request $request, PurchaseRequest $purchaseRequest, DecidePurchaseRequest $decide): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'note' => ['nullable', 'required_if:decision,reject', 'string', 'max:500'],
            'approved' => ['array'],
            'approved.*' => ['nullable', 'integer', 'min:0'],
        ], [
            'note.required_if' => 'Tulis alasan penolakan agar pemohon tahu apa yang perlu diubah.',
        ]);

        $note = $request->filled('note') ? $request->string('note')->trim()->toString() : null;

        if ($request->input('decision') === 'reject') {
            $decide->reject($purchaseRequest, $user, (string) $note);

            return to_route('purchase-requests.show', $purchaseRequest)->with('success', "{$purchaseRequest->number} ditolak.");
        }

        $decide->approve($purchaseRequest, $user, QuantityInput::from($request, 'approved'), $note);

        return to_route('purchase-requests.show', $purchaseRequest)->with('success', "{$purchaseRequest->number} disetujui dan diteruskan ke pengadaan.");
    }
}
