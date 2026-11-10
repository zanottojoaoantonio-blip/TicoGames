<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        Product::findOrFail($this->route('id'));

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:10000'],
            'image_path' => ['sometimes', 'required', 'string', 'max:255'],
            'platform_id' => ['sometimes', 'required', 'integer', 'exists:platforms,id'],
            'price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'genre_ids' => ['sometimes', 'array'],
            'genre_ids.*' => ['required', 'integer', 'distinct', 'exists:genres,id'],
        ];
    }
}
