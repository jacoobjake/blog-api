<?php

namespace App\Http\Requests\AuthorProfile;

use App\Enums\Role;
use App\Models\AuthorProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $authorProfile = $this->route('authorProfile');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'user' => ['nullable', 'array'],
            'user.link' => ['required_with:user', Rule::in(['none', 'existing', 'new'])],
            'user.user_id' => [
                'required_if:user.link,existing',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('author_profiles', 'user_id')->ignore($authorProfile?->id),
            ],
            'user.name' => ['required_if:user.link,new', 'string', 'max:255'],
            'user.email' => [
                'required_if:user.link,new',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'user.password' => ['required_if:user.link,new', 'string', 'min:8'],
            'user.roles' => ['nullable', 'array'],
            'user.roles.*' => ['string', Rule::in(array_column(Role::cases(), 'value'))],
        ];
    }
}
