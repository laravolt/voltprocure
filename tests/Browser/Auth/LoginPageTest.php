<?php

declare(strict_types=1);

it('can display login page', function (): void {
    $page = visit('/auth/login');

    $page->assertSee('Login')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('has email field on login page', function (): void {
    $page = visit('/auth/login');

    $page->assertSee('Email');
});

it('has password field on login page', function (): void {
    $page = visit('/auth/login');

    $page->assertSee('Password');
});

it('has forgot password link on login page', function (): void {
    $page = visit('/auth/login');

    $page->assertSeeLink('Forgot password');
});

it('redirects root to login page', function (): void {
    $page = visit('/');

    $page->assertPathIs('/auth/login')
        ->assertSee('Login');
});
