<?php

declare(strict_types=1);

use App\Models\User;

it('can display edit password page', function (): void {
    $this->actingAs(User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]));

    $page = visit('/my/password');

    $page->assertSee('Edit Password')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('has current password field', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/password');

    $page->assertSee('Current Password');
});

it('has new password field', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/password');

    $page->assertSee('New Password');
});

it('has confirm new password field', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/password');

    $page->assertSee('Confirm New Password');
});

it('has save button on password page', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/password');

    $page->assertSee('Save');
});
