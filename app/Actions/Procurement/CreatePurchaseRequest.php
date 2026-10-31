<?php

declare(strict_types=1);

namespace App\Actions\Procurement;

use App\Enums\Permission;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class CreatePurchaseRequest
{
    public function __construct(private DocumentNumber $numbers) {}

    /**
     * @param  array{purpose: string, needed_by?: string|null, lines: list<array{item_id: string, quantity: int}>}  $attributes
     */
    public function handle(User $requester, array $attributes): PurchaseRequest
    {
        Gate::forUser($requester)->authorize(Permission::PURCHASE_REQUEST_CREATE);

        if ($requester->department_id === null) {
            throw ValidationException::withMessages(['department' => 'Akun Anda belum terdaftar di departemen mana pun.']);
        }

        return DB::transaction(function () use ($requester, $attributes): PurchaseRequest {
            $request = PurchaseRequest::query()->create([
                'number' => $this->numbers->next(PurchaseRequest::class, 'PR'),
                'department_id' => $requester->department_id,
                'requester_id' => $requester->getKey(),
                'purpose' => $attributes['purpose'],
                'needed_by' => $attributes['needed_by'] ?? null,
                'status' => PurchaseRequestStatus::Draft,
            ]);

            $items = Item::query()->whereKey(array_column($attributes['lines'], 'item_id'))->get()->keyBy('id');

            foreach ($attributes['lines'] as $line) {
                $request->lines()->create([
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'estimated_unit_cost' => $items[$line['item_id']]->standard_cost ?? 0,
                ]);
            }

            return $request;
        });
    }
}
