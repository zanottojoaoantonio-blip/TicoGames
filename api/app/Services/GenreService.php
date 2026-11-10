<?php

namespace App\Services;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class GenreService
{
    public function listAll(): Collection
    {
        $genres = Genre::withCount('products')->orderBy('name')->get();

        return $genres;
    }

    public function findById(int $id): Genre
    {
        $genre = Genre::withCount('products')->findOrFail($id);

        return $genre;
    }

    public function create(array $data): Genre
    {
        $genre = Genre::create($data);

        $genre = $genre->loadCount('products');

        return $genre;
    }

    public function update(int $id, array $data): Genre
    {
        $genre = Genre::findOrFail($id);
        $genre->update($data);

        $genre = $genre->refresh()->loadCount('products');

        return $genre;
    }

    public function delete(int $id): void
    {
        $genre = Genre::findOrFail($id);

        if ($genre->products()->exists()) {
            throw ValidationException::withMessages([
                'genre' => 'Não é possível excluir um gênero que possui produtos associados.',
            ]);
        }

        $genre->delete();
    }
}
