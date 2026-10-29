<?php

namespace App\Services;

// Concentra as operações de plataformas, sem montar respostas HTTP.
use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PlatformService
{
    // withCount() inclui products_count sem carregar os produtos inteiros.
    public function listAll(): Collection
    {
        return Platform::withCount('products')->orderBy('name')->get();
    }

    // Busca pelo ID e inclui a contagem; findOrFail() gera 404 se não encontrar.
    public function findById(int $id): Platform
    {
        return Platform::withCount('products')->findOrFail($id);
    }

    // Cria o cadastro; a validação dos dados será feita na etapa de FormRequests.
    public function create(array $data): Platform
    {
        $platform = Platform::create($data);

        return $platform->loadCount('products');
    }

    // Localiza o registro, atualiza os campos recebidos e devolve sua contagem.
    public function update(int $id, array $data): Platform
    {
        $platform = Platform::findOrFail($id);
        $platform->update($data);

        return $platform->refresh()->loadCount('products');
    }

    // Preserva os produtos: os vínculos devem ser ajustados antes da exclusão.
    public function delete(int $id): void
    {
        $platform = Platform::findOrFail($id);

        if ($platform->products()->exists()) {
            throw ValidationException::withMessages([
                'platform' => 'Não é possível excluir uma plataforma que possui produtos associados.',
            ]);
        }

        // Sem vínculos, o cadastro pode ser removido fisicamente.
        $platform->delete();
    }
}
