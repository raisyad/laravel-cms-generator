@extends('layouts.app')

@section('content')
<h1>Edit {{ ucfirst('posts') }}</h1>
<form method="POST" action="{{ route('posts.update', $post) }}">
  @csrf @method('PUT')
  @foreach ((new \App\Models\Post)->getFillable() as $col)
    <div>
      <label>{{ $col }}</label>
      <input type="text" name="{{ $col }}" value="{{ old($col, $post->{$col}) }}">
      @error($col) <small>{{ $message }}</small> @enderror
    </div>
  @endforeach
  <button type="submit">Update</button>
</form>
@endsection
