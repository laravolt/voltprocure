<?php

declare(strict_types=1);

use App\Models\User;

it('has no smoke on guest pages', function (): void {
    $pages = visit([
        '/auth/login',
        '/auth/forgot',
        '/auth/register',
        '/auth/reset/fake-token',
    ]);

    $pages->assertNoSmoke();
})->group('critical');

it('has no smoke on authenticated pages', function (): void {
    $this->actingAs(User::factory()->create());

    $pages = visit([
        '/home',
        '/my/profile',
        '/my/password',
    ]);

    $pages->assertNoSmoke();
})->group('critical');
