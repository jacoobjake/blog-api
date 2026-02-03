<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    protected ?Model $model;

    protected static string $modelClass = Model::class;

    public function __construct(Model|int|string|null $model = null)
    {
        $this->initializeModel($model);
    }

    # PROTECTED METHODS
    protected function initializeModel(Model|int|string|null $model = null): static
    {
        match (true) {
            is_numeric($model), is_string($model) => $this->setModelById($model),
            $model instanceof Model => $this->setModel($model),
            default => $this->model = null,
        };

        return $this;
    }

    # PUBLIC METHODS
    public function delete(): static
    {
        if (!$this->model) {
            throw new \LogicException("Model is not initialized");
        }

        $this->model->delete();
        $this->model = null;

        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getModelClass(): string
    {
        return static::$modelClass;
    }

    public function getQuery()
    {
        return static::$modelClass::query();
    }

    public function store(array $data): static
    {
        $this->model = new static::$modelClass($data);
        $this->model->save();

        return $this;
    }

    public function update(array $data): static
    {
        if (!$this->model) {
            throw new \LogicException("Model is not initialized");
        }

        $this->model->update($data);

        return $this;
    }

    public function setModelById(int|string $modelId): static
    {
        $this->model = $this->model::findOrFail($modelId);
        return $this;
    }

    public function setModel(Model $model): static
    {
        if (!$model instanceof static::$modelClass) {
            throw new \InvalidArgumentException("Invalid model type");
        }
        $this->model = $model;

        return $this;
    }
}