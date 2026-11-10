<?php

namespace App\Http\Requests;

use App\Models\Genre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $genre = Genre::findOrFail($this->route('id'));

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('genres', 'name')->ignore($genre),
            ],
        ];
    }
}
