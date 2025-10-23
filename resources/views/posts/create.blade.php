@extends('layouts.app')

@section('content')
<h1>Create {{ ucfirst('posts') }}</h1>
<form method="POST" action="{{ route('posts.store') }}">
  @csrf
  @foreach ((new \App\Models\Post)->getFillable() as $col)
    <div>
      <label>{{ $col }}</label>
      <input type="text" name="{{ $col }}" value="{{ old($col) }}">
      @error($col) <small>{{ $message }}</small> @enderror
    </div>
  @endforeach
  <button type="submit">Save</button>
</form>
@endsection
