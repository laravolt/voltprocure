<?php

declare(strict_types=1);

use App\Models\User;

it('can display edit profile page', function (): void {
    $this->actingAs(User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]));

    $page = visit('/my/profile');

    $page->assertSee('Edit Profile')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('has name field on profile page', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/profile');

    $page->assertSee('Name');
});

it('has email field on profile page', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/profile');

    $page->assertSee('Email');
});

it('has timezone field on profile page', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/profile');

    $page->assertSee('Timezone');
});

it('has save button on profile page', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/profile');

    $page->assertSee('Save');
});
