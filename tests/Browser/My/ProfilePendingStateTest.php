<?php

declare(strict_types=1);

use App\Models\User;

it('locks the profile form while a real Livewire save request is pending', function (): void {
    $user = User::factory()->create([
        'name' => 'Before',
        'timezone' => 'UTC',
    ]);

    $this->actingAs($user);

    $page = visit('/my/profile');

    $page
        ->assertSee('Edit Profile')
        ->fill('input[wire\\:model="name"]', 'After')
        ->assertScript('window.Livewire !== undefined')
        ->assertScript('document.querySelector("[data-update-uri]")?.getAttribute("data-update-uri")?.length > 0');

    $page->script(<<<'JS'
        const originalFetch = window.fetch.bind(window);
        const updateUri = document.querySelector('[data-update-uri]').getAttribute('data-update-uri');
        window.__profileRequestDelayed = false;
        window.__releasePendingProfileRequest = null;

        window.fetch = (...args) => {
            const input = args[0];
            const url = typeof input === 'string' ? input : (input?.url ?? '');

            if (url === updateUri && !window.__profileRequestDelayed) {
                window.__profileRequestDelayed = true;

                return new Promise((resolve, reject) => {
                    window.__releasePendingProfileRequest = () => {
                        originalFetch(...args).then(resolve, reject);
                    };
                });
            }

            return originalFetch(...args);
        };
    JS);

    $page->script("document.querySelector('[data-profile-save]').click();");

    $page
        ->assertButtonDisabled('[data-profile-save]')
        ->assertScript('document.querySelector("[data-profile-fields]").disabled === true')
        ->assertVisible('[data-profile-save-loading]')
        ->assertAttribute('[data-profile-form]', 'aria-busy', 'true');

    $page->script('window.__releasePendingProfileRequest();');

    $page
        ->assertSee('Profil berhasil diperbarui')
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->name)->toBe('After');
})->group('critical');
