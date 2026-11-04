<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    // Evita executar eventos dos Models durante a preparação dos dados.
    use WithoutModelEvents;

    /**
     * Executa os seeders na ordem exigida pelos relacionamentos.
     */
    public function run(): void
    {
        // Se algum seeder falhar, desfaz somente os dados desta execução.
        DB::transaction(function () {
            $this->call([
                UserSeeder::class, // Cria as contas antes dos endereços.
                CategorySeeder::class, // Prepara as classificações do catálogo.
                PlatformSeeder::class, // Produtos precisam de uma plataforma.
                GenreSeeder::class, // Prepara os gêneros dos jogos.
                ProductSeeder::class, // Cria produtos e suas associações.
                AddressSeeder::class, // Acrescenta endereços ao cliente de exemplo.
            ]);
        });
    }
}
