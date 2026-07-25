<?php

namespace App\Http\Requests\Blog;

class UpdateBlogRequest extends CreateBlogRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('blog'));
    }
}
