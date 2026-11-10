<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['PlayStation 5', 'Xbox Series', 'Nintendo Switch'] as $name) {
            Platform::firstOrCreate(['name' => $name]);
        }
    }
}
