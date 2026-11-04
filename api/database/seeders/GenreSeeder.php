<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        // Lista fixa e pequena para demonstrar os filtros de jogos futuramente.
        foreach (['Ação', 'Aventura', 'RPG', 'Corrida'] as $name) {
            // O primeiro argumento informa como identificar um gênero existente.
            Genre::firstOrCreate(['name' => $name]);
        }
    }
}
