<?php

declare(strict_types=1);

use App\Livewire\My\ProfileForm;
use App\Models\User;
use Livewire\Livewire;

it('renders the authenticated user profile', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timezone' => 'UTC',
    ]);

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->assertSet('name', 'Test User')
        ->assertSet('email', 'test@example.com')
        ->assertSet('timezone', 'UTC')
        ->assertSee('wire:offline', false)
        ->assertSee('wire:loading.attr="aria-busy"', false)
        ->assertSee('wire:target="save"', false);
});

it('updates the authenticated user profile', function (): void {
    $user = User::factory()->create([
        'name' => 'Before',
        'timezone' => 'UTC',
    ]);

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('name', 'After')
        ->set('timezone', 'Asia/Jakarta')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Profil berhasil diperbarui');

    $user->refresh();

    expect($user->name)->toBe('After')
        ->and($user->timezone)->toBe('Asia/Jakarta');
});

it('rejects a stale profile component after the authenticated user changes', function (): void {
    $owner = User::factory()->create(['name' => 'Owner']);
    $otherUser = User::factory()->create(['name' => 'Other User']);

    $component = Livewire::actingAs($owner)
        ->test(ProfileForm::class)
        ->set('name', 'Stale Update');

    $this->actingAs($otherUser);

    $component
        ->call('save')
        ->assertForbidden();

    expect($owner->refresh()->name)->toBe('Owner')
        ->and($otherUser->refresh()->name)->toBe('Other User');
});

it('validates required profile fields', function (string $property): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set($property, '')
        ->call('save')
        ->assertHasErrors([$property => ['required']]);
})->with(['name', 'timezone']);

it('keeps a functional HTTP fallback on the profile form', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('my::profile.edit'))
        ->assertSuccessful()
        ->assertSee('method="POST"', false)
        ->assertSee('action="'.route('my::profile.update').'"', false)
        ->assertSee('name="_method" value="PUT"', false)
        ->assertSee('name="timezone"', false)
        ->assertSeeLivewire(ProfileForm::class);
});

it('preserves old input after HTTP fallback validation fails', function (): void {
    $user = User::factory()->create([
        'name' => 'Persisted Name',
        'timezone' => 'UTC',
    ]);

    $this->actingAs($user)
        ->from(route('my::profile.edit'))
        ->put(route('my::profile.update'), [
            'name' => 'Submitted Name',
            'timezone' => '',
        ])
        ->assertRedirect(route('my::profile.edit'))
        ->assertSessionHasErrors('timezone');

    $this->get(route('my::profile.edit'))
        ->assertSuccessful()
        ->assertSee('value="Submitted Name"', false);

    expect($user->refresh()->name)->toBe('Persisted Name');
});
