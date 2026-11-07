<?php

declare(strict_types=1);

use App\Http\Middleware\RejectRevokedSession;
use App\Models\User;
use Illuminate\Routing\Router;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

test('the web group signs out other sessions after a password change and rejects revoked sessions', function (): void {
    expect(resolve(Router::class)->getMiddlewareGroups()['web'])
        ->toContain('auth.session')
        ->toContain(RejectRevokedSession::class);
});

test('logout revokes the session it ends', function (): void {
    /** @var TestCase */
    $test = $this;
    $sessionId = Str::random(40);

    $test->actingAs(User::factory()->create())
        ->withCookie(config('session.cookie'), $sessionId)
        ->post(route('auth::logout'))
        ->assertRedirect('/');

    expect(Cache::has('revoked-session:'.hash('sha256', $sessionId)))->toBeTrue();
});

test('a revoked session is signed out on its next request', function (): void {
    /** @var TestCase */
    $test = $this;
    $sessionId = Str::random(40);
    $store = resolve(SessionManager::class)->driver();
    $store->setId($sessionId);
    RejectRevokedSession::revoke($store);

    $test->actingAs(User::factory()->create())
        ->withCookie(config('session.cookie'), $sessionId)
        ->get(route('home'))
        ->assertRedirect();

    $test->assertGuest();
});
