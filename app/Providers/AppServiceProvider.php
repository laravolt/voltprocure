<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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
        // The demo's documents are Indonesian; dates follow (Oktober, Sen, ...).
        Date::setLocale('id');

        $this->app->make('laravolt.acl')->registerPermission(Permission::asArray());

        // Every Laravolt page (ours and the built-in ones) gets the design layer.
        View::composer('laravolt::layout.base', function (ViewInstance $view): void {
            $tags = resolve(Vite::class)(['resources/css/app.css'])->toHtml();

            // startPush() with empty content opens an output buffer instead (withoutVite() in tests).
            if ($tags !== '') {
                $view->getFactory()->startPush('style', $tags);
            }
        });

        foreach (Permission::getValues() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}
