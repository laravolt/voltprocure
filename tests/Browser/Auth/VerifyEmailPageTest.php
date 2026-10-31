<?php

declare(strict_types=1);

use App\Models\User;

it('can display email verification page for unverified user', function (): void {
    $this->actingAs(User::factory()->unverified()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]));

    $page = visit('/auth/verify-email');

    $page->assertSee('Verifikasi Email')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('has resend verification button', function (): void {
    $this->actingAs(User::factory()->unverified()->create());

    $page = visit('/auth/verify-email');

    $page->assertSee('Kirim Ulang Email Verifikasi');
});

it('has logout link on verification page', function (): void {
    $this->actingAs(User::factory()->unverified()->create());

    $page = visit('/auth/verify-email');

    $page->assertSee('Logout');
});
