<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Define os campos de gênero apresentados nas respostas da API.
class GenreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // Inclui a contagem somente quando ela já foi carregada pelo Service.
            'products_count' => $this->whenCounted('products'),
            // Apresenta as datas em um formato consistente para o frontend.
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
