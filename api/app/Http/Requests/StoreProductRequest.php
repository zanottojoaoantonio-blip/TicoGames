<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'image_path' => ['required', 'string', 'max:255'],
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'boolean'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'genre_ids' => ['sometimes', 'array'],
            'genre_ids.*' => ['required', 'integer', 'distinct', 'exists:genres,id'],
        ];
    }
}
