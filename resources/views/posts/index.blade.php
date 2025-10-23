@extends('layouts.app')

@section('content')
<h1>{{ ucfirst('posts') }} - Index</h1>
<a href="{{ route('posts.create') }}">Create</a>

<table border="1" cellpadding="6">
  <thead>
    <tr>
      <th>ID</th>
      @foreach ((new \App\Models\Post)->getFillable() as $col)
        <th>{{ $col }}</th>
      @endforeach
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($items as $item)
      <tr>
        <td>{{ $item->id }}</td>
        @foreach ((new \App\Models\Post)->getFillable() as $col)
          <td>{{ $item->{$col} }}</td>
        @endforeach
        <td>
          <a href="{{ route('posts.show', $item) }}">Show</a>
          <a href="{{ route('posts.edit', $item) }}">Edit</a>
          <form action="{{ route('posts.destroy', $item) }}" method="POST" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" onclick="return confirm('Delete?')">Delete</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

{{ $items->links() }}
@endsection
