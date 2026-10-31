<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Wipes the database and replays the demo story. The seeder dates every
 * document relative to now, so a nightly reset keeps the demo current.
 */
#[Signature('demo:reset {--force : Run even when APP_DEMO is off}')]
#[Description('Rebuild the VoltProcure demo data from scratch')]
final class ResetDemo extends Command
{
    public function handle(): int
    {
        if (! config()->boolean('app.demo') && ! $this->option('force')) {
            $this->components->error('APP_DEMO is off; refusing to wipe this database. Pass --force to override.');

            return self::FAILURE;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->components->info('Demo data rebuilt.');

        return self::SUCCESS;
    }
}
