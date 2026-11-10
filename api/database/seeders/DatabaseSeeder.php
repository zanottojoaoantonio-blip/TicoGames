<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            $this->call([
                UserSeeder::class,
                CategorySeeder::class,
                PlatformSeeder::class,
                GenreSeeder::class,
                ProductSeeder::class,
                AddressSeeder::class,
                StoreScenarioSeeder::class,
            ]);
        });
    }
}
