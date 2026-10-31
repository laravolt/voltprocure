<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void
{
    // ..
}

/**
 * @param  list<string>  $permissions
 * @param  array<string, mixed>  $attributes
 */
function procurementUser(array $permissions, array $attributes = []): User
{
    $role = resolve(config('laravolt.epicentrum.models.role'))->create(['name' => fake()->unique()->word()]);
    $role->syncPermission($permissions);

    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user->fresh();
}
