<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    // As permissões de acesso serão configuradas na etapa de autenticação.
    public function authorize(): bool
    {
        return true;
    }

    // O Laravel valida antes do Controller; erros de entrada retornam 422.
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'platform_id' => ['sometimes', 'required', 'integer', 'exists:platforms,id'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'image_path' => ['sometimes', 'required', 'string', 'max:255'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'category_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'genre_ids' => ['sometimes', 'array'],
            'genre_ids.*' => ['required', 'integer', 'distinct', 'exists:genres,id'],
        ];
    }
}
