<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida as colunas do produto e os IDs usados nas tabelas de ligação.
class StoreProductRequest extends FormRequest
{
    // A restrição ao administrador será implementada com Sanctum na etapa 8.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Os textos respeitam os limites das colunas e do cadastro.
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'image_path' => ['required', 'string', 'max:255'],
            // A plataforma precisa existir antes de cadastrar o produto.
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            // O preço cabe em decimal(10,2); estoque não pode ser negativo.
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'boolean'],
            // Exige ao menos uma categoria e valida cada ID sem repetições.
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            // Gêneros são opcionais; consoles podem não ter nenhum.
            'genre_ids' => ['sometimes', 'array'],
            'genre_ids.*' => ['required', 'integer', 'distinct', 'exists:genres,id'],
        ];
    }
}
