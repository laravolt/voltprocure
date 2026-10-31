<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class ForgotPasswordController extends Controller
{
    /**
     * Display the form to request a password reset link.
     */
    public function show(): View
    {
        return view('auth.forgot');
    }

    /**
     * Send a reset link to the given user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = $request->string('email')->toString();

        // Attempt to send reset link - this will silently fail for non-existent users
        $user = User::whereEmail($email)->first();
        if ($user) {
            resolve('laravolt.password')->sendResetLink($user);
        }

        /** @var array<string, string> $translationParams */
        $translationParams = ['email' => $email, 'emailMasked' => Str::maskEmail($email)];

        // Always return the same success message to prevent user enumeration
        return to_route('auth::forgot.show')
            ->with('success', trans(Password::RESET_LINK_SENT, $translationParams));
    }
}
