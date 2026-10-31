<?php

declare(strict_types=1);

namespace App\Http\Controllers\My;

use App\Actions\My\UpdateProfile;
use App\Http\Requests\My\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('my.profile.edit');
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var array{name: string, timezone: string} $validated */
        $validated = $request->validated();
        $updateProfile->handle($user, $validated);

        return back()->withSuccess(__('Profil berhasil diperbarui'));
    }
}
