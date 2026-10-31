<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('refuses to wipe the database unless demo mode is on', function (): void {
    config(['app.demo' => false]);
    $user = User::factory()->create();

    $this->artisan('demo:reset')->assertFailed();

    expect(User::query()->whereKey($user->getKey())->exists())->toBeTrue();
});

it('schedules the reset nightly at midnight Jakarta time', function (): void {
    $event = collect(resolve(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'demo:reset'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *')
        ->and($event->timezone)->toBe('Asia/Jakarta');
});
