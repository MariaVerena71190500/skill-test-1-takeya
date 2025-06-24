<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_only_active_posts()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_posts_create_route_returns_expected_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/posts/create');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'posts.create',
        ]);
    }

    public function test_authenticated_user_can_create_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ];

        $response = $this->postJson('/posts', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'title' => 'Test Title',
            'content' => 'Test Content',
        ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Title',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_view_active_post_as_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $post->id,
            'title' => $post->title,
        ]);
    }

    public function test_draft_post_returns_404_on_show()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertStatus(404);
    }

    public function test_scheduled_post_returns_404_on_show()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertStatus(404);
    }

    public function test_posts_edit_route_returns_expected_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/posts/{$post->id}/edit");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'posts.edit',
        ]);
    }

    public function test_author_can_update_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'content' => 'Old Content',
            'is_draft' => true,
        ]);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ];

        $response = $this->putJson("/posts/{$post->id}", $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Post updated successfully.',
            'title' => 'Updated Title',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_non_author_cannot_update_post()
    {
        $author = User::factory()->create();
        $nonAuthor = User::factory()->create();
        $this->actingAs($nonAuthor);

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->putJson("/posts/{$post->id}", [
            'title' => 'Hacked Title',
            'content' => 'Hacked Content',
            'is_draft' => false,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
            'title' => 'Hacked Title',
        ]);
    }

    public function test_author_can_delete_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/posts/{$post->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Post deleted successfully.',
        ]);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_non_author_cannot_delete_post()
    {
        $author = User::factory()->create();
        $nonAuthor = User::factory()->create();
        $this->actingAs($nonAuthor);

        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->deleteJson("/posts/{$post->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
        ]);
    }
}
