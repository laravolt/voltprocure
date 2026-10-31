<?php

declare(strict_types=1);

use App\Http\Middleware\Authenticate;

arch()->preset()->php();
// Ignore framework/package internals; this starter enforces strictness on app code.
arch()->preset()->strict()->ignoring([Authenticate::class, 'Illuminate', 'Laravel', 'Laravolt']);
arch()->preset()->security()->ignoring(['Illuminate', 'Laravel', 'Laravolt']);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

//
