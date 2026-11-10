<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Platform;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Elden Ring',
                'platform' => 'PlayStation 5',
                'description' => 'Jogo físico de ação e RPG para PlayStation 5.',
                'price' => '299.90',
                'stock' => 8,
                'categories' => ['Jogos'],
                'genres' => ['Ação', 'RPG'],
            ],
            [
                'name' => 'Elden Ring',
                'platform' => 'Xbox Series',
                'description' => 'Jogo físico de ação e RPG para Xbox Series.',
                'price' => '279.90',
                'stock' => 6,
                'categories' => ['Jogos'],
                'genres' => ['Ação', 'RPG'],
            ],
            [
                'name' => 'Mario Kart 8 Deluxe',
                'platform' => 'Nintendo Switch',
                'description' => 'Jogo físico de corrida para Nintendo Switch.',
                'price' => '329.90',
                'stock' => 10,
                'categories' => ['Jogos'],
                'genres' => ['Corrida'],
            ],
            [
                'name' => 'The Legend of Zelda: Tears of the Kingdom',
                'platform' => 'Nintendo Switch',
                'description' => 'Jogo físico de ação e aventura para Nintendo Switch.',
                'price' => '359.90',
                'stock' => 5,
                'categories' => ['Jogos'],
                'genres' => ['Ação', 'Aventura'],
            ],
            [
                'name' => 'PlayStation 5',
                'platform' => 'PlayStation 5',
                'description' => 'Console PlayStation 5 com leitor de discos.',
                'price' => '3499.90',
                'stock' => 3,
                'categories' => ['Consoles'],
                'genres' => [],
            ],
            [
                'name' => 'Xbox Series X',
                'platform' => 'Xbox Series',
                'description' => 'Console Xbox Series X com leitor de discos.',
                'price' => '3999.90',
                'stock' => 4,
                'categories' => ['Consoles'],
                'genres' => [],
            ],
            [
                'name' => 'Nintendo Switch',
                'platform' => 'Nintendo Switch',
                'description' => 'Console Nintendo Switch compatível com jogos físicos.',
                'price' => '2199.90',
                'stock' => 4,
                'categories' => ['Consoles'],
                'genres' => [],
            ],
        ];

        $titles = ['Hogwarts Legacy', 'Resident Evil 4', 'Sonic Frontiers', 'Minecraft', 'Stardew Valley', 'Hades', 'Dead Cells'];
        foreach ($titles as $index => $name) {
            foreach (['PlayStation 5', 'Xbox Series', 'Nintendo Switch'] as $platform) {
                $products[] = ['name' => $name, 'platform' => $platform,
                    'description' => $name.' para '.$platform.'. Produto fictício para demonstração.',
                    'price' => '199.90', 'stock' => $index === 0 ? 0 : 10,
                    'is_active' => $index !== 1, 'categories' => ['Jogos'], 'genres' => ['Ação']];
            }
        }
        foreach (['Controle sem fio', 'Headset estéreo'] as $name) {
            $products[] = ['name' => $name, 'platform' => 'PlayStation 5',
                'description' => $name.' de demonstração.', 'price' => '149.90', 'stock' => 10,
                'categories' => ['Acessórios'], 'genres' => []];
        }

        foreach ($products as $data) {
            $platform = Platform::where('name', $data['platform'])->firstOrFail();

            $product = Product::firstOrCreate(
                ['name' => $data['name'], 'platform_id' => $platform->id],
                [
                    'category_id' => Category::where('name', $data['categories'][0])->firstOrFail()->id,
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'image_path' => 'images/product-placeholder.svg',
                    'stock' => $data['stock'],
                    'is_active' => $data['is_active'] ?? true,
                ],
            );

            foreach ($data['genres'] as $name) {
                $genre = Genre::where('name', $name)->firstOrFail();
                $product->genres()->syncWithoutDetaching([$genre->id]);
            }
        }
    }
}
