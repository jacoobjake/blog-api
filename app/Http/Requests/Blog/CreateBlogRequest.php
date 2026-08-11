<?php

namespace App\Http\Requests\Blog;

use App\Enums\BlogJsonContentType;
use App\Enums\Permission;
use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateBlogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Blog::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'json_content' => ['required', 'array'],
            'json_content.type' => ['required', Rule::enum(BlogJsonContentType::class)],
            'json_content.body' => BlogJsonContentType::getContentValidationRule($this->input('json_content.type') ?? ''),
            'hero_asset_uuid' => [
                'nullable',
                'uuid',
                Rule::exists('assets', 'uuid')->where('user_id', auth()->id()),
            ],
            'author_profile_id' => ['required', 'integer', Rule::exists('author_profiles', 'id')],
            'is_published' => ['required', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ["required", 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            $authorProfileId = (int) $this->input('author_profile_id');

            if ($user->can(Permission::AUTHORS_VIEW_ANY->value)) {
                return;
            }

            $ownProfile = $user->authorProfile;

            if ($ownProfile === null || $ownProfile->id !== $authorProfileId) {
                $validator->errors()->add(
                    'author_profile_id',
                    __('validation.author_profile_assignment_forbidden'),
                );
            }
        });
    }
}
