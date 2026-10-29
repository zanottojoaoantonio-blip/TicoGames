<?php

namespace App\Services;

// Concentra as operações de categorias, sem montar respostas HTTP.
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    // withCount() inclui products_count sem carregar os produtos inteiros.
    public function getAll(): Collection
    {
        return Category::withCount('products')->orderBy('name')->get();
    }

    // Carrega a quantidade de produtos no cadastro encontrado pela rota.
    public function getOne(Category $category): Category
    {
        return $category->loadCount('products');
    }

    // O Controller já validou os campos que serão gravados pelo Model.
    public function create(array $data): Category
    {
        return $this->getOne(Category::create($data));
    }

    // Atualiza os campos recebidos e retorna o registro com sua contagem.
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $this->getOne($category->refresh());
    }

    // Preserva os produtos: os vínculos devem ser ajustados antes da exclusão.
    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Não é possível excluir uma categoria que possui produtos associados.',
            ]);
        }

        // Sem vínculos, o cadastro pode ser removido fisicamente.
        $category->delete();
    }
}
