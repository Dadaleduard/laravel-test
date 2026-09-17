<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_attach_tags_to_post()
    {
        $post = Post::factory()->for($this->user)->create();
        $tags = Tag::factory(2)->create();

        $response = $this->postJson("/api/posts/{$post->id}/tags", [
            'tag_ids' => $tags->pluck('id')->all(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.tags');

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('post_tag', [
                'post_id' => $post->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_can_detach_tag_from_post()
    {
        $post = Post::factory()->for($this->user)->create();
        $tags = Tag::factory(2)->create();
        $post->tags()->attach($tags->pluck('id')->all());

        $response = $this->deleteJson("/api/posts/{$post->id}/tags/{$tags[0]->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tags[0]->id,
        ]);

        $this->assertDatabaseHas('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tags[1]->id,
        ]);
    }

    public function test_can_read_post_with_tags()
    {
        $post = Post::factory()->for($this->user)->create();
        $tags = Tag::factory(3)->create();
        $post->tags()->attach($tags->pluck('id')->all());

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.tags');
        $response->assertJsonPath('data.tags.0.name', $tags[0]->name);
        $response->assertJsonPath('data.tags.0.slug', $tags[0]->slug);
    }

    public function test_attaching_unknown_tag_fails_validation()
    {
        $post = Post::factory()->for($this->user)->create();

        $response = $this->postJson("/api/posts/{$post->id}/tags", [
            'tag_ids' => [999],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tag_ids.0');

        $this->assertDatabaseCount('post_tag', 0);
    }

    public function test_attaching_same_tag_twice_does_not_duplicate()
    {
        $post = Post::factory()->for($this->user)->create();
        $tag = Tag::factory()->create();

        $this->postJson("/api/posts/{$post->id}/tags", ['tag_ids' => [$tag->id]]);
        $response = $this->postJson("/api/posts/{$post->id}/tags", ['tag_ids' => [$tag->id]]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.tags');
        $this->assertDatabaseCount('post_tag', 1);
    }
}
