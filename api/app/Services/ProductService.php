<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function listAll(bool $includeInactive = true, array $filters = []): LengthAwarePaginator
    {
        $query = Product::with(['platform', 'category', 'genres'])->when(! $includeInactive, fn ($query) => $query->where('is_active', true));
        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }
        foreach (['category_id', 'platform_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['genre_id'])) {
            $query->whereHas('genres', fn ($query) => $query->where('genres.id', $filters['genre_id']));
        }
        match ($filters['sort'] ?? 'id') {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderBy('id'),
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->orderBy('id'),
        };

        return $query->paginate($filters['per_page'] ?? 12)->withQueryString();
    }

    public function findById(int $id, bool $includeInactive = true): Product
    {
        return Product::with(['platform', 'category', 'genres'])->when(! $includeInactive, fn ($query) => $query->where('is_active', true))->findOrFail($id);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $genreIds = $data['genre_ids'] ?? [];
            unset($data['genre_ids']);
            $product = Product::create($data);
            $product->genres()->sync($genreIds);

            return $this->findById($product->id);
        });
    }

    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();
            $genreIds = $data['genre_ids'] ?? null;
            unset($data['genre_ids']);
            $product->update($data);
            if ($genreIds !== null) {
                $product->genres()->sync($genreIds);
            }

            return $this->findById($product->id);
        });
    }

    public function upload(UploadedFile $image): string
    {
        $path = $image->store('products', 'public');
        abort_unless($path, 500, 'Não foi possível salvar a imagem.');

        return '/storage/'.$path;
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->update(['is_active' => false]);
    }
}
