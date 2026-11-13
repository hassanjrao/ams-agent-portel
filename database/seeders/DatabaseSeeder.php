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
        // Reference data every environment needs.
        $this->call(ReferenceDataSeeder::class);

        // Logins and sample groups for trying the portal out: never on the live server.
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
