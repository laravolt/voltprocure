<?php

declare(strict_types=1);

return [
    /*
     * Keep Boost's "Test Enforcement" guideline on without letting Boost count
     * the tests itself. To count them, `boost:install` and `boost:update` run
     * `php artisan test --list-tests`, which loads tests/Browser and starts a
     * Playwright server that Pest never stops in that mode.
     */
    'enforce_tests' => true,
];
