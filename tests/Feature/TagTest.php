<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_tags()
    {
        Tag::factory(3)->create();

        $response = $this->getJson('/api/tags');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure(['data' => [['id', 'name', 'slug']]]);
    }

    public function test_can_show_tag()
    {
        $tag = Tag::factory()->create();

        $response = $this->getJson("/api/tags/{$tag->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $tag->id);
        $response->assertJsonPath('data.slug', $tag->slug);
    }

    public function test_can_create_tag()
    {
        $response = $this->postJson('/api/tags', ['name' => 'Laravel']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Laravel');
        $response->assertJsonPath('data.slug', 'laravel');

        $this->assertDatabaseHas('tags', ['name' => 'Laravel', 'slug' => 'laravel']);
    }

    public function test_slug_is_generated_from_name_when_omitted()
    {
        $response = $this->postJson('/api/tags', ['name' => 'Back End Dev']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.slug', 'back-end-dev');
    }

    public function test_cannot_create_tag_with_duplicate_slug()
    {
        Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

        $response = $this->postJson('/api/tags', ['name' => 'PHP']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
        $this->assertDatabaseCount('tags', 1);
    }

    public function test_creating_tag_without_name_fails_validation()
    {
        $response = $this->postJson('/api/tags', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_creating_tag_with_non_string_name_fails_validation()
    {
        foreach ([['a', 'b'], 123, ['x' => 1]] as $name) {
            $response = $this->postJson('/api/tags', ['name' => $name]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors('name');
        }

        $this->assertDatabaseCount('tags', 0);
    }

    public function test_can_update_tag()
    {
        $tag = Tag::factory()->create(['name' => 'Old', 'slug' => 'old']);

        $response = $this->putJson("/api/tags/{$tag->id}", ['name' => 'New Name']);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'New Name');
        $response->assertJsonPath('data.slug', 'new-name');

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'slug' => 'new-name']);
    }

    public function test_can_update_tag_keeping_its_own_slug()
    {
        $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

        $response = $this->putJson("/api/tags/{$tag->id}", ['name' => 'PHP', 'slug' => 'php']);

        $response->assertStatus(200);
        $response->assertJsonPath('data.slug', 'php');
    }

    public function test_cannot_update_tag_to_an_existing_slug()
    {
        Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $response = $this->putJson("/api/tags/{$tag->id}", ['slug' => 'php']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
    }

    public function test_can_delete_tag()
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/tags/{$tag->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_deleting_tag_removes_it_from_posts()
    {
        $post = Post::factory()->for($this->user)->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag->id);

        $this->deleteJson("/api/tags/{$tag->id}")->assertStatus(204);

        $this->assertDatabaseCount('post_tag', 0);
        $this->assertCount(0, $post->fresh()->tags);
    }

    public function test_unknown_tag_returns_not_found()
    {
        $this->getJson('/api/tags/999')->assertStatus(404);
    }
}
