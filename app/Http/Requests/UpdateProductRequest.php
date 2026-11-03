<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

// Permite atualizar somente os campos enviados, mantendo as regras do cadastro.
class UpdateProductRequest extends FormRequest
{
    // A restrição ao administrador será implementada com Sanctum na etapa 8.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Confirma a existência antes de validar os campos; um ID ausente gera 404.
        Product::findOrFail($this->route('id'));

        return [
            // sometimes permite omitir; required impede enviar um valor vazio.
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:10000'],
            'image_path' => ['sometimes', 'required', 'string', 'max:255'],
            'platform_id' => ['sometimes', 'required', 'integer', 'exists:platforms,id'],
            'price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            // Uma lista enviada substitui os vínculos, mas categorias não podem ficar vazias.
            'category_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            // [] remove todos os gêneros; omitir a lista preserva os vínculos atuais.
            'genre_ids' => ['sometimes', 'array'],
            'genre_ids.*' => ['required', 'integer', 'distinct', 'exists:genres,id'],
        ];
    }
}
