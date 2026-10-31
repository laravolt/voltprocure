<?php

declare(strict_types=1);

it('can display registration page', function (): void {
    $page = visit('/auth/register');

    $page->assertSee('Register')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('has name field on registration page', function (): void {
    $page = visit('/auth/register');

    $page->assertSee('Name');
});

it('has email field on registration page', function (): void {
    $page = visit('/auth/register');

    $page->assertSee('Email');
});

it('has password field on registration page', function (): void {
    $page = visit('/auth/register');

    $page->assertSee('Password');
});

it('has login link on registration page', function (): void {
    $page = visit('/auth/register');

    $page->assertSeeLink('Login Here');
});
