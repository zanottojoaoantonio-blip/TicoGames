<?php

namespace App\Services;

// Concentra as operações de categorias, sem montar respostas HTTP.
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    // withCount() inclui products_count sem carregar os produtos inteiros.
    public function listAll(): Collection
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return $categories;
    }

    // Busca pelo ID e inclui a contagem; findOrFail() gera 404 se não encontrar.
    public function findById(int $id): Category
    {
        $category = Category::withCount('products')->findOrFail($id);

        return $category;
    }

    // Cria o cadastro usando os campos validados pelo FormRequest.
    public function create(array $data): Category
    {
        $category = Category::create($data);

        $category = $category->loadCount('products');

        return $category;
    }

    // Localiza o registro, atualiza os campos recebidos e devolve sua contagem.
    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);
        $category->update($data);

        $category = $category->refresh()->loadCount('products');

        return $category;
    }

    // Preserva os produtos: os vínculos devem ser ajustados antes da exclusão.
    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Não é possível excluir uma categoria que possui produtos associados.',
            ]);
        }

        // Sem vínculos, o cadastro pode ser removido fisicamente.
        $category->delete();
    }
}
