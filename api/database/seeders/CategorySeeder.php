<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Jogos', 'Consoles', 'Acessórios'] as $name) {
            // Procura pelo nome e cria somente se ainda não houver essa categoria.
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
