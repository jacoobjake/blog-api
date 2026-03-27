<?php

namespace Tests\Unit\Services;

use App\Models\Blog;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

// Concrete implementation using Blog so we can test with a real model
class ConcreteService extends BaseService
{
    protected static string $modelClass = Blog::class;
}

class BaseServiceTest extends TestCase
{
    private ConcreteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConcreteService();
    }

    // -------------------------------------------------------------------------
    // store()
    // -------------------------------------------------------------------------

    public function test_store_persists_model_and_returns_static(): void
    {
        $user = User::factory()->create();
        $result = $this->service->store([
            'title' => 'Test Blog',
            'json_content' => ['type' => 'compressed_base64', 'body' => 'abc'],
            'author' => 'Author',
            'is_published' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertInstanceOf(ConcreteService::class, $result);
        $this->assertDatabaseHas('blogs', ['title' => 'Test Blog']);
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_changes_model_attributes(): void
    {
        $blog = Blog::factory()->create(['title' => 'Original Title']);
        $this->service->setModel($blog)->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('blogs', ['title' => 'Updated Title']);
        $this->assertDatabaseMissing('blogs', ['title' => 'Original Title']);
    }

    public function test_update_throws_when_model_not_initialized(): void
    {
        $this->expectException(\LogicException::class);

        $this->service->update(['title' => 'Should Fail']);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function test_delete_removes_model_from_database(): void
    {
        $blog = Blog::factory()->create();
        $this->service->setModel($blog)->delete();

        $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
    }

    public function test_delete_sets_model_to_null(): void
    {
        $blog = Blog::factory()->create();
        $this->service->setModel($blog)->delete();

        $this->assertNull($this->service->getModel());
    }

    public function test_delete_throws_when_model_not_initialized(): void
    {
        $this->expectException(\LogicException::class);

        $this->service->delete();
    }

    // -------------------------------------------------------------------------
    // getModel()
    // -------------------------------------------------------------------------

    public function test_get_model_returns_set_model(): void
    {
        $blog = Blog::factory()->create();
        $this->service->setModel($blog);

        $this->assertSame($blog->id, $this->service->getModel()->id);
    }

    public function test_get_model_returns_null_when_not_initialized(): void
    {
        $this->assertNull($this->service->getModel());
    }

    // -------------------------------------------------------------------------
    // getQuery()
    // -------------------------------------------------------------------------

    public function test_get_query_returns_eloquent_builder_for_model_class(): void
    {
        $query = $this->service->getQuery();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
        $this->assertSame(Blog::class, get_class($query->getModel()));
    }

    // -------------------------------------------------------------------------
    // getModelClass()
    // -------------------------------------------------------------------------

    public function test_get_model_class_returns_model_class_string(): void
    {
        $this->assertSame(Blog::class, $this->service->getModelClass());
    }

    // -------------------------------------------------------------------------
    // setModelById()
    // -------------------------------------------------------------------------

    public function test_set_model_by_id_loads_correct_model(): void
    {
        $user = User::factory()->create();
        $blog = Blog::factory()->createdBy($user)->create();

        $result = $this->service->setModelById($blog->id);

        $this->assertInstanceOf(ConcreteService::class, $result);
        $this->assertSame($blog->id, $this->service->getModel()->id);
    }

    public function test_set_model_by_id_throws_for_unknown_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->setModelById(999999);
    }

    // -------------------------------------------------------------------------
    // setModel()
    // -------------------------------------------------------------------------

    public function test_set_model_accepts_correct_model_type(): void
    {
        $blog = Blog::factory()->create();
        $result = $this->service->setModel($blog);

        $this->assertSame($blog->id, $this->service->getModel()->id);
        $this->assertInstanceOf(ConcreteService::class, $result);
    }

    public function test_set_model_throws_for_wrong_model_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::factory()->create();
        $this->service->setModel($user);
    }

    // -------------------------------------------------------------------------
    // initializeModel()
    // -------------------------------------------------------------------------

    public function test_initialize_model_with_null_leaves_model_as_null(): void
    {
        $service = new ConcreteService(null);

        $this->assertNull($service->getModel());
    }

    public function test_initialize_model_with_model_instance_sets_model(): void
    {
        $blog = Blog::factory()->create();
        $service = new ConcreteService($blog);

        $this->assertSame($blog->id, $service->getModel()->id);
    }
}
