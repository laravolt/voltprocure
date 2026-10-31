<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('laravolt:admin admin admin@laravolt.dev secret');

        $this->call(ProcurementDemoSeeder::class);
    }
}
