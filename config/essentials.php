<?php

declare(strict_types=1);

use NunoMaduro\Essentials\Configurables\ForceScheme;
use NunoMaduro\Essentials\Configurables\Unguard;

return [
    Unguard::class => true,

    // In production every URL is forced to https. Turn it off only when the app is served over plain http,
    // for example `docker compose up` on localhost.
    ForceScheme::class => (bool) env('APP_FORCE_HTTPS', true),
];
