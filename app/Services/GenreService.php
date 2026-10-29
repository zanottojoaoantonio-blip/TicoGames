<?php

namespace App\Services;

// Concentra as operações de gêneros, sem montar respostas HTTP.
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class GenreService
{
    // withCount() inclui products_count sem carregar os produtos inteiros.
    public function getAll(): Collection
    {
        return Genre::withCount('products')->orderBy('name')->get();
    }

    // Carrega a quantidade de produtos no cadastro encontrado pela rota.
    public function getOne(Genre $genre): Genre
    {
        return $genre->loadCount('products');
    }

    // O Controller já validou os campos que serão gravados pelo Model.
    public function create(array $data): Genre
    {
        return $this->getOne(Genre::create($data));
    }

    // Atualiza os campos recebidos e retorna o registro com sua contagem.
    public function update(Genre $genre, array $data): Genre
    {
        $genre->update($data);

        return $this->getOne($genre->refresh());
    }

    // Preserva os produtos: os vínculos devem ser ajustados antes da exclusão.
    public function delete(Genre $genre): void
    {
        if ($genre->products()->exists()) {
            throw ValidationException::withMessages([
                'genre' => 'Não é possível excluir um gênero que possui produtos associados.',
            ]);
        }

        // Sem vínculos, o cadastro pode ser removido fisicamente.
        $genre->delete();
    }
}
