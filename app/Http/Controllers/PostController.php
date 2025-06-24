<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = Post::with('user')
            ->active()
            ->paginate(20);

        return response()->json($posts);
    }

    public function create()
    {
        return 'posts.create';
    }

    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        Post::create([
            'user_id' => auth()->id(),
            ...$data,
        ]);

        return redirect()->route('dashboard');
    }

    public function show(Post $post)
    {
        if ($post->is_draft || $post->published_at > now()) {
            abort(404);
        }

        return response()->json($post);
    }

    public function edit(Post $post)
    {
        return 'posts.edit';
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validated();

        $post->update($data);

        return redirect()->route('dashboard');
    }

    public function destroy(Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $post->delete();

        return redirect()->route('dashboard')->with('success', 'Post deleted successfully.');
    }
}
