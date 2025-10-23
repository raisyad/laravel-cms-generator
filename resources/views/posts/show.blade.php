@extends('layouts.app')

@section('content')
<h1>Detail {{ ucfirst('posts') }}</h1>
<ul>
  <li>ID: {{ $post->id }}</li>
  @foreach ((new \App\Models\Post)->getFillable() as $col)
    <li>{{ $col }}: {{ $post->{$col} }}</li>
  @endforeach
</ul>
@endsection
