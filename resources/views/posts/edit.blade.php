@extends('layouts.cms')

@section('content')
<h1>Edit {{ ucfirst('posts') }}</h1>

<form method="POST" action="{{ route('cms.posts.update', $post) }}">
  @csrf
  @method('PUT')
  <div>
  <label>title</label>
  <input type="text" name="title" value="{{ old('title', $post->title ?? '') }}">
  @error('title') <small>{{ $message }}</small> @enderror
</div>
<div>
  <label>body</label>
  <input type="text" name="body" value="{{ old('body', $post->body ?? '') }}">
  @error('body') <small>{{ $message }}</small> @enderror
</div>
<div>
  <label>published_at</label>
  <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at ?? '') }}">
  @error('published_at') <small>{{ $message }}</small> @enderror
</div>

  <button type="submit">Update</button>
</form>
@endsection
