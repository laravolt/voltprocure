<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    Artisan::call('laravolt:admin admin admin@laravolt.dev secret');

    $this->adminUser = User::query()->where('email', 'admin@laravolt.dev')->firstOrFail();
});

it('can display roles page', function (): void {
    $this->actingAs($this->adminUser);

    $page = visit('/epicentrum/roles');

    $page->assertSee('Roles')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('can display create role page', function (): void {
    $this->actingAs($this->adminUser);

    $page = visit('/epicentrum/roles/create');

    $page->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('can display permissions page', function (): void {
    $this->actingAs($this->adminUser);

    $page = visit('/epicentrum/permissions');

    $page->assertSee('Permissions')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});
