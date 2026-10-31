<?php

declare(strict_types=1);

use App\Models\User;

it('can display home page for authenticated user', function (): void {
    $this->actingAs(User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]));

    $page = visit('/home');

    $page->assertPathIs('/home')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});
