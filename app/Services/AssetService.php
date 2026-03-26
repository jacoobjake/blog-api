<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AssetService extends BaseService
{
    protected static string $modelClass = Asset::class;

    public function setByUuid(string $uuid): static
    {
        $this->model = static::$modelClass::where('uuid', $uuid)->firstOrFail();
        return $this;
    }

    public function store(array $data): static
    {
        $data['uuid'] = Str::uuid()->toString();
        $data['user_id'] = auth()->id();

        return parent::store($data);
    }

    public function uploadMedia(UploadedFile $file): static
    {
        if (!$this->model) {
            throw new \LogicException("Model is not initialized");
        }

        $this->model->addMedia($file)
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection($this->model->type->value);
        return $this;
    }

    public function delete(): static
    {
        if (!$this->model) {
            throw new \LogicException("Model is not initialized");
        }

        // Clears all Spatie media files + conversions before deleting the record
        $this->model->clearMediaCollection($this->model->type->value);

        return parent::delete();
    }
}