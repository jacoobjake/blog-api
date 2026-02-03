<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\CreateBlogRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
use App\Models\Blog;
use App\Services\BlogService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function __construct(protected BlogService $blogService) {}


    public function store(CreateBlogRequest $request)
    {
        $blog = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $data = Arr::except($validated, 'tags');
            $tags = $validated['tags'] ?? [];
            return $this->blogService->store($data)
                ->syncTags($tags)
                ->getModel();
        });
        return $this->success($this->modelActionMessage($blog, 'created'), [
            'slug' => $blog->slug,
        ]);
    }

    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        $blog = DB::transaction(function () use ($request, $blog) {
            $validated = $request->validated();
            $data = Arr::except($validated, 'tags');
            $tags = $validated['tags'] ?? [];
            return $this->blogService->setModel($blog)
                ->update($data)
                ->syncTags($tags)
                ->getModel();
        });

        return $this->success($this->modelActionMessage($blog, 'updated'), [
            'slug' => $blog->slug,
        ]);
    }

    public function destroy(Blog $blog)
    {
        DB::transaction(function () use ($blog) {
            $this->blogService->setModel($blog)->delete();
        });

        return $this->success($this->modelActionMessage($blog, 'deleted'));
    }
}
