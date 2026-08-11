<?php

namespace App\Http\Requests\AuthorProfile;

use App\Models\AuthorProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnAuthorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->user()->authorProfile;

        return $profile !== null && $this->user()->can('update', $profile);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
        ];
    }
}
