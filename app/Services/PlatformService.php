<?php

namespace App\Services;

// Concentra as operações de plataformas, sem montar respostas HTTP.
use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PlatformService
{
    // withCount() inclui products_count sem carregar os produtos inteiros.
    public function getAll(): Collection
    {
        return Platform::withCount('products')->orderBy('name')->get();
    }

    // Carrega a quantidade de produtos no cadastro encontrado pela rota.
    public function getOne(Platform $platform): Platform
    {
        return $platform->loadCount('products');
    }

    // O Controller já validou os campos que serão gravados pelo Model.
    public function create(array $data): Platform
    {
        return $this->getOne(Platform::create($data));
    }

    // Atualiza os campos recebidos e retorna o registro com sua contagem.
    public function update(Platform $platform, array $data): Platform
    {
        $platform->update($data);

        return $this->getOne($platform->refresh());
    }

    // Preserva os produtos: os vínculos devem ser ajustados antes da exclusão.
    public function delete(Platform $platform): void
    {
        if ($platform->products()->exists()) {
            throw ValidationException::withMessages([
                'platform' => 'Não é possível excluir uma plataforma que possui produtos associados.',
            ]);
        }

        // Sem vínculos, o cadastro pode ser removido fisicamente.
        $platform->delete();
    }
}
