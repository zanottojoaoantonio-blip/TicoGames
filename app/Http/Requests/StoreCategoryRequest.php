<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ];
    }
}
