<?php

declare(strict_types=1);

namespace App\Livewire\My;

use App\Actions\My\UpdateProfile;
use App\Http\Requests\My\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Laravolt\Support\Contracts\TimezoneRepository;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ProfileForm extends Component
{
    #[Locked]
    public string $userId = '';

    public string $name = '';

    #[Locked]
    public string $email = '';

    public string $timezone = '';

    private TimezoneRepository $timezoneRepository;

    public function boot(TimezoneRepository $timezoneRepository): void
    {
        $this->timezoneRepository = $timezoneRepository;
    }

    public function mount(): void
    {
        $user = $this->authenticatedUser();

        $this->userId = $this->stringAttribute($user, $user->getKeyName());
        $this->name = $this->oldString('name', $this->stringAttribute($user, 'name'));
        $this->email = $this->stringAttribute($user, 'email');
        $this->timezone = $this->oldString('timezone', $this->stringAttribute($user, 'timezone'));
    }

    public function save(UpdateProfile $updateProfile): void
    {
        $user = User::query()->findOrFail($this->userId);

        Gate::allowIf(static fn (User $authenticatedUser): bool => $authenticatedUser->is($user));

        /** @var array{name: string, timezone: string} $validated */
        $validated = $this->validate(UpdateProfileRequest::profileRules());

        $updateProfile->handle($user, $validated);

        session()->flash('success', __('Profil berhasil diperbarui'));
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function timezones(): array
    {
        return Cache::remember('profile.timezones', now()->addHour(), function (): array {
            /** @var array<string, string> $timezones */
            $timezones = $this->timezoneRepository->all();

            return $timezones;
        });
    }

    public function render(): View
    {
        return view('livewire.my.profile-form');
    }

    private function authenticatedUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function oldString(string $key, string $default): string
    {
        if (! session()->hasOldInput($key)) {
            return $default;
        }

        $value = old($key);

        return is_string($value) ? $value : '';
    }

    private function stringAttribute(User $user, string $attribute): string
    {
        $value = $user->getAttribute($attribute);

        return is_string($value) ? $value : '';
    }
}
