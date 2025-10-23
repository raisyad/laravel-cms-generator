<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;

class PostApiController extends Controller
{
    public function index()
    {
        return PostResource::collection(Post::latest()->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([]); // bisa diisi rules otomatis di iterasi lanjut
        $item = Post::create($data);
        return new PostResource($item);
    }

    public function show(Post $post)
    {
        return new PostResource($post);
    }

    public function update(Request $request, Post $post)
    {
        $data = $request->validate([]);
        $post->update($data);
        return new PostResource($post);
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return response()->noContent();
    }
}
