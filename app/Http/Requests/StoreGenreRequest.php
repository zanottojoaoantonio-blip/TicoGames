<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Valida os dados de cadastro de gênero antes de executar o Controller.
class StoreGenreRequest extends FormRequest
{
    // As rotas verificam autenticação e perfil antes de executar o FormRequest.
    public function authorize(): bool
    {
        return true;
    }

    // O nome é obrigatório, deve ser texto e não pode se repetir nesta tabela.
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:genres,name'],
        ];
    }
}
