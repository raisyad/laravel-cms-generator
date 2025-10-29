<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StoreRequest;
use App\Http\Requests\Post\UpdateRequest;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', \App\Models\Post::class);

        $q = request('q');
        $query = \App\Models\Post::query();

        if ($q) {
            $cols = ['title', 'body', 'published_at'];
            $query->where(function ($w) use ($q, $cols) {
                foreach ($cols as $col) {
                    $w->orWhere($col, 'like', '%'.$q.'%');
                }
            });
        }

        $posts = $query->latest()->paginate()->withQueryString();

        return view('posts.index', compact('posts', 'q'));
    }

    public function create()
    {
        $this->authorize('create', \App\Models\Post::class);

        return view('posts.create');
    }

    public function store(StoreRequest $request)
    {
        $this->authorize('create', \App\Models\Post::class);

        $post = Post::create($request->validated());

        return redirect()->route('cms.posts.index')
            ->with('success', 'Post created');
    }

    public function show(Post $post)
    {
        $this->authorize('view', $post);

        return view('posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        return view('posts.edit', compact('post'));
    }

    public function update(UpdateRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $post->update($request->validated());

        return redirect()->route('cms.posts.index')
            ->with('success', 'Post updated');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return back()->with('success', 'Post deleted');
    }

    public function restore($id)
    {
        $post = \App\Models\Post::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $post);

        $post->restore();

        return back()->with('success', 'Post restored');
    }
}
