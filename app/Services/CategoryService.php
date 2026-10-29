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
        return Category::withCount('products')->orderBy('name')->get();
    }

    // Busca pelo ID e inclui a contagem; findOrFail() gera 404 se não encontrar.
    public function findById(int $id): Category
    {
        return Category::withCount('products')->findOrFail($id);
    }

    // Cria o cadastro; a validação dos dados será feita na etapa de FormRequests.
    public function create(array $data): Category
    {
        $category = Category::create($data);

        return $category->loadCount('products');
    }

    // Localiza o registro, atualiza os campos recebidos e devolve sua contagem.
    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);
        $category->update($data);

        return $category->refresh()->loadCount('products');
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
