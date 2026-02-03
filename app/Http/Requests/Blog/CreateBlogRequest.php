<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class CreateBlogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'json_content' => ['required', 'array'],
            'author' => ['required', 'string'],
            'is_published' => ['required', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ["required", 'string', 'max:255'],
        ];
    }
}
