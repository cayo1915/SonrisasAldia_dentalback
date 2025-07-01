<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call([
            TimeZoneSeed::class,
            ChargeSeed::class,
            RoleSeed::class,
            DetailMenuItemsSeed::class,
            DaySeed::class,
            UserSeeder::class,
            ToothModelSeed::class
        ]);
    }
}
