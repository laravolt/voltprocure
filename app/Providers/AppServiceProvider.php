<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     */
    public const string HOME = '/home';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make('laravolt.acl')->registerPermission(Permission::asArray());

        foreach (Permission::getValues() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}
