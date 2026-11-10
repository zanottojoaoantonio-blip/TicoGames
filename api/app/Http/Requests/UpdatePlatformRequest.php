<?php

namespace App\Http\Requests;

use App\Models\Platform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $platform = Platform::findOrFail($this->route('id'));

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('platforms', 'name')->ignore($platform),
            ],
        ];
    }
}
