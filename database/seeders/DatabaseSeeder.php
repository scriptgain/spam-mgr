<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Filtering policies are seeded by `spam:bootstrap`, which the installer
        // runs, so a fresh install has working thresholds without a seeder step.

    }
}
