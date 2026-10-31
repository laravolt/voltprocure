<?php

declare(strict_types=1);

it('can display forgot password page', function (): void {
    $page = visit('/auth/forgot');

    $page->assertSee('Forgot Password')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('has email field on forgot password page', function (): void {
    $page = visit('/auth/forgot');

    $page->assertSee('Email');
});
