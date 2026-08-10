<?php

namespace App\Services;

use App\Enums\TagType;
use App\Models\AuthorProfile;
use App\Models\Blog;

class BlogService extends BaseService
{
    protected static string $modelClass = Blog::class;

    public function store(array $data): static
    {
        $user_id = auth()->id();
        $data['created_by'] = $user_id;
        $data['updated_by'] = $user_id;

        return parent::store($data);
    }

    public function update(array $data): static
    {
        $data['updated_by'] = auth()->id();

        return parent::update($data);
    }

    public function syncTags(array $tags = []): static
    {
        $this->model->syncTagsWithType($tags, TagType::BLOG->value);

        return $this;
    }

    public function syncAuthorProfile(array $data): static
    {
        $profileData = [
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
        ];

        if ($this->model->authorProfile) {
            $this->model->authorProfile->update($profileData);
        } else {
            $profile = AuthorProfile::create($profileData);
            $this->model->update(['author_profile_id' => $profile->id]);
        }

        return $this;
    }
}