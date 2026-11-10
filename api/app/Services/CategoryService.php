<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function listAll(): Collection
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return $categories;
    }

    public function findById(int $id): Category
    {
        $category = Category::withCount('products')->findOrFail($id);

        return $category;
    }

    public function create(array $data): Category
    {
        $category = Category::create($data);

        $category = $category->loadCount('products');

        return $category;
    }

    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);
        $category->update($data);

        $category = $category->refresh()->loadCount('products');

        return $category;
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Não é possível excluir uma categoria que possui produtos associados.',
            ]);
        }

        $category->delete();
    }
}
