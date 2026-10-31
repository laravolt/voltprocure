<?php

declare(strict_types=1);

it('redirects unauthenticated user from home to login', function (): void {
    $page = visit('/home');

    $page->assertSee('Login');
})->group('critical');

it('redirects unauthenticated user from profile to login', function (): void {
    $page = visit('/my/profile');

    $page->assertSee('Login');
})->group('critical');

it('redirects unauthenticated user from password to login', function (): void {
    $page = visit('/my/password');

    $page->assertSee('Login');
})->group('critical');
