<?php

declare(strict_types=1);

namespace App\Actions\Procurement;

use App\Enums\Permission;
use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Approves (fully or per line) or rejects a submitted purchase request.
 * Only the manager of the request's department, or a user with every
 * permission, may decide.
 */
final class DecidePurchaseRequest
{
    /**
     * @param  array<string, int>  $approvedQuantities  line id => approved quantity; missing lines keep the requested quantity
     */
    public function approve(PurchaseRequest $request, User $approver, array $approvedQuantities = [], ?string $note = null): void
    {
        $this->guard($request, $approver);

        DB::transaction(function () use ($request, $approver, $approvedQuantities, $note): void {
            foreach ($request->lines as $line) {
                $approved = $approvedQuantities[$line->id] ?? $line->quantity;

                if ($approved < 0 || $approved > $line->quantity) {
                    throw ValidationException::withMessages(['lines' => 'Jumlah disetujui harus antara 0 dan jumlah yang diminta.']);
                }

                $line->update(['approved_quantity' => $approved]);
            }

            if ($request->lines()->where('approved_quantity', '>', 0)->doesntExist()) {
                throw ValidationException::withMessages(['lines' => 'Setujui minimal satu barang, atau tolak permintaan ini.']);
            }

            $this->decide($request, $approver, PurchaseRequestStatus::Approved, $note);
        });
    }

    public function reject(PurchaseRequest $request, User $approver, string $note): void
    {
        $this->guard($request, $approver);

        $this->decide($request, $approver, PurchaseRequestStatus::Rejected, $note);
    }

    private function guard(PurchaseRequest $request, User $approver): void
    {
        Gate::forUser($approver)->authorize(Permission::PURCHASE_REQUEST_APPROVE);

        $isManager = $request->department()->firstOrFail()->manager_id === $approver->getKey();

        throw_if(! $isManager && ! $approver->hasPermission('*'), AuthorizationException::class, 'Hanya manajer departemen ini yang dapat memutuskan.');

        if ($request->status !== PurchaseRequestStatus::Submitted) {
            throw ValidationException::withMessages(['status' => 'Permintaan ini tidak sedang menunggu persetujuan.']);
        }
    }

    private function decide(PurchaseRequest $request, User $approver, PurchaseRequestStatus $status, ?string $note): void
    {
        $request->update([
            'status' => $status,
            'decided_by' => $approver->getKey(),
            'decided_at' => Date::now(),
            'decision_note' => $note,
        ]);
    }
}
