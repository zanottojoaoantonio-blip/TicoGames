<?php

namespace App\Services;

// O Service concentra a consulta e a gravação, sem depender de requisições HTTP.
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    // Lista todos os cadastros, inclusive inativos, para conferir o CRUD administrativo.
    // with() carrega os relacionamentos e evita consultar cada associação separadamente.
    public function getAll(): Collection
    {
        return Product::with(['platform', 'categories', 'genres'])->orderBy('id')->get();
    }

    // Acrescenta os relacionamentos ao produto que o Laravel encontrou pela rota.
    public function getOne(Product $product): Product
    {
        return $product->load(['platform', 'categories', 'genres']);
    }

    // Cadastra os campos do produto e as associações em uma única transação.
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // IDs de categorias e gêneros pertencem às tabelas de ligação.
            $categoryIds = $data['category_ids'];
            $genreIds = $data['genre_ids'] ?? [];
            unset($data['category_ids'], $data['genre_ids']);

            // Salva somente as colunas que pertencem à tabela products.
            $product = Product::create($data);

            // sync() grava exatamente as associações informadas no cadastro.
            $product->categories()->sync($categoryIds);
            $product->genres()->sync($genreIds);

            // refresh() também lê padrões definidos pelo banco, como is_active = true.
            return $this->getOne($product->refresh());
        });
    }

    // Atualiza os campos enviados, preservando os que não foram informados.
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            // null significa que a lista não foi enviada; [] permite retirar todos os gêneros.
            $categoryIds = $data['category_ids'] ?? null;
            $genreIds = $data['genre_ids'] ?? null;
            unset($data['category_ids'], $data['genre_ids']);

            $product->update($data);

            // Só substitui as associações quando a respectiva lista vier na requisição.
            if ($categoryIds !== null) {
                $product->categories()->sync($categoryIds);
            }

            if ($genreIds !== null) {
                $product->genres()->sync($genreIds);
            }

            return $this->getOne($product->refresh());
        });
    }

    // Retira o produto de oferta, preservando seu ID, associações e histórico.
    public function delete(Product $product): void
    {
        $product->update(['is_active' => false]);
    }
}
