<?php

namespace App\Http\Requests\AuthorProfile;

use App\Models\AuthorProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnAuthorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
        ];
    }
}
