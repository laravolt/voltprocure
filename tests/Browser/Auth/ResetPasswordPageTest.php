<?php

declare(strict_types=1);

it('can display reset password page', function (): void {
    $page = visit('/auth/reset/fake-token');

    $page->assertSee('Reset Password')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('has email field on reset password page', function (): void {
    $page = visit('/auth/reset/fake-token');

    $page->assertSee('Email');
});

it('has new password field on reset password page', function (): void {
    $page = visit('/auth/reset/fake-token');

    $page->assertSee('New Password');
});

it('has confirm password field on reset password page', function (): void {
    $page = visit('/auth/reset/fake-token');

    $page->assertSee('Confirm New Password');
});
