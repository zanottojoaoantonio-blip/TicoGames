<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform_id' => $this->platform_id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image_path' => preg_match('~^https?://~i', $this->image_path) ? $this->image_path : url($this->image_path),
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'platform' => new PlatformResource($this->whenLoaded('platform')),
            'categories' => CategoryResource::collection($this->whenLoaded('category', fn () => collect([$this->category]))),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
