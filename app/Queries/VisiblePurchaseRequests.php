<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Permission;
use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who sees which purchase requests: procurement sees all of them, a
 * department manager sees the department's, everyone else only their own.
 */
final class VisiblePurchaseRequests
{
    /**
     * @return Builder<PurchaseRequest>
     */
    public function for(User $user): Builder
    {
        $query = PurchaseRequest::query();

        if ($user->hasPermission('*') || $user->hasPermission(Permission::PURCHASE_ORDER_MANAGE)) {
            return $query;
        }

        $managed = Department::query()->where('manager_id', $user->getKey())->pluck('id');

        return $query->where(
            fn (Builder $visible): Builder => $visible
                ->where('requester_id', $user->getKey())
                ->orWhereIn('department_id', $managed),
        );
    }

    public function canDecide(User $user, PurchaseRequest $request): bool
    {
        if (! $user->hasPermission(Permission::PURCHASE_REQUEST_APPROVE) && ! $user->hasPermission('*')) {
            return false;
        }

        return $user->hasPermission('*')
            || $request->department()->value('manager_id') === $user->getKey();
    }
}
