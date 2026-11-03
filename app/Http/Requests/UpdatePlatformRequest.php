<?php

namespace App\Http\Requests;

use App\Models\Platform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Valida os campos enviados na atualização de plataforma.
class UpdatePlatformRequest extends FormRequest
{
    // As permissões de acesso serão configuradas na etapa de autenticação.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Busca o ID da rota; um registro inexistente deve responder 404.
        $platform = Platform::findOrFail($this->route('id'));

        return [
            // O nome pode ser omitido; se enviado, precisa estar preenchido.
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                // Ignora somente o registro encontrado, permitindo manter o próprio nome.
                Rule::unique('platforms', 'name')->ignore($platform),
            ],
        ];
    }
}
