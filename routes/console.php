<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Public demo: start every day with a fresh, current story.
Schedule::command('demo:reset')
    ->dailyAt('00:00')
    ->timezone('Asia/Jakarta')
    ->when(fn (): bool => config()->boolean('app.demo'))
    ->withoutOverlapping();
