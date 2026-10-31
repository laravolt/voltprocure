<?php

declare(strict_types=1);

namespace App\Actions\My;

use App\Models\User;

final class UpdateProfile
{
    /**
     * @param  array{name: string, timezone: string}  $attributes
     */
    public function handle(User $user, array $attributes): void
    {
        $user->update($attributes);
    }
}
