<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Ação', 'Aventura', 'RPG', 'Corrida'] as $name) {
            Genre::firstOrCreate(['name' => $name]);
        }
    }
}
