<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $posts = Post::with('user')
            ->active()
            ->paginate(20);

        return response()->json($posts);
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'posts.create']);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $post = Post::create($data);

        return response()->json([
            'message' => 'Post created successfully.',
            'post' => $post,
        ], 201);
    }

    public function show(Post $post): JsonResponse
    {
        if (! $post->isActive()) {
            abort(404);
        }

        return response()->json($post);
    }

    public function edit(Post $post): JsonResponse
    {
        return response()->json(['message' => 'posts.edit']);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $post->update($request->validated());

        return response()->json([
            'message' => 'Post updated successfully.',
            'post' => $post,
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.']);
    }
}
