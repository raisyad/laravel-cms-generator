<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\Post\StoreRequest;
use App\Http\Requests\Post\UpdateRequest;

class PostController extends Controller
{
    public function index()
    {
        $items = Post::latest()->paginate();
        return view('posts.index', compact('items'));
    }

    public function create()
    {
        return view('posts.create');
    }

    public function store(StoreRequest $request)
    {
        $item = Post::create($request->validated());
        return redirect()->route('posts.index')->with('success', 'Post created');
    }

    public function show(Post $post)
    {
        return view('posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        return view('posts.edit', compact('post'));
    }

    public function update(UpdateRequest $request, Post $post)
    {
        $post->update($request->validated());
        return redirect()->route('posts.index')->with('success', 'Post updated');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return back()->with('success', 'Post deleted');
    }
}
