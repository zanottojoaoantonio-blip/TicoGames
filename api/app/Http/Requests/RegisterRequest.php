<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    // O cadastro é público; o perfil da conta será sempre customer.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // confirmed compara password com password_confirmation.
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
