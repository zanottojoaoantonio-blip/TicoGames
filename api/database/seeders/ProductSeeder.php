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
        // Dados de demonstração: preços e estoques são fictícios, sem consulta a lojas.
        // Preços são textos decimais para não usar números de ponto flutuante.
        $products = [
            [
                'name' => 'Elden Ring',
                'platform' => 'PlayStation 5',
                'description' => 'Jogo físico de ação e RPG para PlayStation 5.',
                'price' => '299.90',
                'stock' => 8,
                'categories' => ['Jogos', 'Destaques'],
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
                'categories' => ['Jogos', 'Destaques'],
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
                'categories' => ['Consoles', 'Destaques'],
                'genres' => [], // Consoles não precisam de gênero.
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
                'categories' => ['Consoles', 'Destaques'],
                'genres' => [],
            ],
        ];

        foreach ($products as $data) {
            // Busca o ID da plataforma; falha se o seeder anterior não a criou.
            $platform = Platform::where('name', $data['platform'])->firstOrFail();

            // Nome e plataforma identificam cada exemplo, sem fixar IDs no código.
            // Os valores do segundo argumento só são usados na criação.
            $product = Product::firstOrCreate(
                ['name' => $data['name'], 'platform_id' => $platform->id],
                [
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'image_path' => 'images/product-placeholder.svg',
                    'stock' => $data['stock'],
                    'is_active' => true,
                ],
            );

            // Acrescenta as associações sem duplicar nem remover vínculos existentes.
            foreach ($data['categories'] as $name) {
                $category = Category::where('name', $name)->firstOrFail();
                $product->categories()->syncWithoutDetaching([$category->id]);
            }

            // A lista vazia dos consoles simplesmente não executa este laço.
            foreach ($data['genres'] as $name) {
                $genre = Genre::where('name', $name)->firstOrFail();
                $product->genres()->syncWithoutDetaching([$genre->id]);
            }
        }
    }
}
