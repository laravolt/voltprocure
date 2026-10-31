<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

it('captures screenshot of login page', function (): void {
    $page = visit('/auth/login');

    $page->assertSee('Login')
        ->screenshot(true, '01-login');
});

it('captures screenshot of registration page', function (): void {
    $page = visit('/auth/register');

    $page->assertSee('Register')
        ->screenshot(true, '02-register');
});

it('captures screenshot of forgot password page', function (): void {
    $page = visit('/auth/forgot');

    $page->assertSee('Forgot password')
        ->screenshot(true, '03-forgot-password');
});

it('captures screenshot of reset password page', function (): void {
    $page = visit('/auth/reset/fake-token');

    $page->assertSee('Reset Password')
        ->screenshot(true, '04-reset-password');
});

it('captures screenshot of email verification page', function (): void {
    $this->actingAs(User::factory()->unverified()->create());

    $page = visit('/auth/verify-email');

    $page->assertSee('Verifikasi Email')
        ->screenshot(true, '05-verify-email');
});

it('captures screenshot of home page', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/home');

    $page->assertPathIs('/home')
        ->screenshot(true, '06-home');
});

it('captures screenshot of edit profile page', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/profile');

    $page->assertSee('Edit Profile')
        ->screenshot(true, '07-my-profile');
});

it('captures screenshot of edit password page', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/my/password');

    $page->assertSee('Edit Password')
        ->screenshot(true, '08-my-password');
});

it('captures screenshot of epicentrum roles page', function (): void {
    Artisan::call('laravolt:admin admin admin@laravolt.dev secret');

    $this->actingAs(User::query()->where('email', 'admin@laravolt.dev')->firstOrFail());

    $page = visit('/epicentrum/roles');

    $page->assertSee('Roles')
        ->screenshot(true, '09-epicentrum-roles');
});

it('captures screenshot of epicentrum create role page', function (): void {
    Artisan::call('laravolt:admin admin admin@laravolt.dev secret');

    $this->actingAs(User::query()->where('email', 'admin@laravolt.dev')->firstOrFail());

    $page = visit('/epicentrum/roles/create');

    $page->assertNoJavaScriptErrors()
        ->screenshot(true, '10-epicentrum-create-role');
});

it('captures screenshot of epicentrum permissions page', function (): void {
    Artisan::call('laravolt:admin admin admin@laravolt.dev secret');

    $this->actingAs(User::query()->where('email', 'admin@laravolt.dev')->firstOrFail());

    $page = visit('/epicentrum/permissions');

    $page->assertSee('Permissions')
        ->screenshot(true, '11-epicentrum-permissions');
});
