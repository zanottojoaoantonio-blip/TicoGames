<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    // Define os campos públicos do JSON, sem expor timestamps ou pivot.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image_path' => $this->image_path,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            // Só apresenta relações já carregadas pelo Service.
            'platform' => new PlatformResource($this->whenLoaded('platform')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
        ];
    }
}
