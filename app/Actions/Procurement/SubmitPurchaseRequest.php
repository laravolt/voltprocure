<?php

declare(strict_types=1);

namespace App\Actions\Procurement;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

final class SubmitPurchaseRequest
{
    public function handle(PurchaseRequest $request, User $requester): void
    {
        if ($request->requester_id !== $requester->getKey()) {
            throw new AuthorizationException('Hanya pemohon yang dapat mengajukan permintaan ini.');
        }

        if ($request->status !== PurchaseRequestStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'Permintaan ini sudah diajukan.']);
        }

        if ($request->lines()->doesntExist()) {
            throw ValidationException::withMessages(['lines' => 'Tambahkan minimal satu barang.']);
        }

        $request->update(['status' => PurchaseRequestStatus::Submitted, 'submitted_at' => Date::now()]);
    }
}
