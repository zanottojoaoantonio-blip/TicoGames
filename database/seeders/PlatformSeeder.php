<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        // As plataformas precisam existir antes de salvar os produtos.
        foreach (['PlayStation 5', 'Xbox Series', 'Nintendo Switch'] as $name) {
            // Reexecutar o seeder não duplica os mesmos nomes.
            Platform::firstOrCreate(['name' => $name]);
        }
    }
}
