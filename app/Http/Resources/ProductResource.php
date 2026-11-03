<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Apresenta as colunas do produto e seus relacionamentos sem expor a tabela pivot.
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform_id' => $this->platform_id,
            'name' => $this->name,
            'description' => $this->description,
            // Os casts do Model mantêm preço como texto decimal e os demais tipos.
            'price' => $this->price,
            'image_path' => $this->image_path,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            // whenLoaded() utiliza apenas os relacionamentos já carregados no Service.
            'platform' => new PlatformResource($this->whenLoaded('platform')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
