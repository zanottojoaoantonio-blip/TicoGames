<?php

namespace App\Services;

// Concentra as operações de gêneros, sem montar respostas HTTP.
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class GenreService
{
    // withCount() inclui products_count sem carregar os produtos inteiros.
    public function listAll(): Collection
    {
        $genres = Genre::withCount('products')->orderBy('name')->get();

        return $genres;
    }

    // Busca pelo ID e inclui a contagem; findOrFail() gera 404 se não encontrar.
    public function findById(int $id): Genre
    {
        $genre = Genre::withCount('products')->findOrFail($id);

        return $genre;
    }

    // Cria o cadastro usando os campos validados pelo FormRequest.
    public function create(array $data): Genre
    {
        $genre = Genre::create($data);

        $genre = $genre->loadCount('products');

        return $genre;
    }

    // Localiza o registro, atualiza os campos recebidos e devolve sua contagem.
    public function update(int $id, array $data): Genre
    {
        $genre = Genre::findOrFail($id);
        $genre->update($data);

        $genre = $genre->refresh()->loadCount('products');

        return $genre;
    }

    // Preserva os produtos: os vínculos devem ser ajustados antes da exclusão.
    public function delete(int $id): void
    {
        $genre = Genre::findOrFail($id);

        if ($genre->products()->exists()) {
            throw ValidationException::withMessages([
                'genre' => 'Não é possível excluir um gênero que possui produtos associados.',
            ]);
        }

        // Sem vínculos, o cadastro pode ser removido fisicamente.
        $genre->delete();
    }
}
