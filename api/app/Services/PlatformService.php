<?php

namespace App\Services;

use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PlatformService
{
    public function listAll(): Collection
    {
        $platforms = Platform::withCount('products')->orderBy('name')->get();

        return $platforms;
    }

    public function findById(int $id): Platform
    {
        $platform = Platform::withCount('products')->findOrFail($id);

        return $platform;
    }

    public function create(array $data): Platform
    {
        $platform = Platform::create($data);

        $platform = $platform->loadCount('products');

        return $platform;
    }

    public function update(int $id, array $data): Platform
    {
        $platform = Platform::findOrFail($id);
        $platform->update($data);

        $platform = $platform->refresh()->loadCount('products');

        return $platform;
    }

    public function delete(int $id): void
    {
        $platform = Platform::findOrFail($id);

        if ($platform->products()->exists()) {
            throw ValidationException::withMessages([
                'platform' => 'Não é possível excluir uma plataforma que possui produtos associados.',
            ]);
        }

        $platform->delete();
    }
}
