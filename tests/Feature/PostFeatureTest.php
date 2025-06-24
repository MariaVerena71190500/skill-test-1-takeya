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

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => 1,
            'published_at' => null,
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => 0,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/posts');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_posts_create_route_returns_expected_string()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('posts.create'));
        $response->assertStatus(200);
        $response->assertSee('posts.create');
    }

    public function test_authenticated_user_can_create_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/posts', [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Title',
            'content' => 'Test Content',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_view_active_post_as_json()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->getJson(route('posts.show', $post));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $post->id,
            'title' => $post->title,
        ]);
    }

    public function test_draft_post_returns_404()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create(['is_draft' => true]);

        $response = $this->getJson(route('posts.show', $post));
        $response->assertStatus(404);
    }

    public function test_posts_edit_route_returns_expected_string()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('posts.edit', $post));

        $response->assertStatus(200);
        $response->assertSeeText('posts.edit');
    }

    public function test_author_can_update_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'content' => 'Old Content',
            'is_draft' => true,
        ]);

        $this->actingAs($user);

        $response = $this->put(route('posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'is_draft' => false,
        ]);
    }

    public function test_non_author_cannot_update_post()
    {
        $author = User::factory()->create();
        $nonAuthor = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $author->id,
        ]);

        $this->actingAs($nonAuthor);

        $response = $this->put(route('posts.update', $post), [
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
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_non_author_cannot_delete_post()
    {
        $author = User::factory()->create();
        $nonAuthor = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($nonAuthor);

        $response = $this->delete(route('posts.destroy', $post));

        $response->assertStatus(403);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
        ]);
    }
}
