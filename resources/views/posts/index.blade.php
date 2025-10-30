@extends('layouts.cms')

@section('content')
{{-- Title --}}
<h1 class="text-3xl font-semibold text-slate-800 mb-6">{{ ucfirst('posts') }} - Index</h1>

{{-- Top bar: Search + Create --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <form method="GET" action="{{ route('cms.posts.index') }}" class="w-full sm:w-auto">

        <div class="flex items-center bg-white rounded-xl shadow-sm ring-1 ring-slate-200 px-4 py-3 w-full sm:w-[480px]">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..." class="w-full bg-transparent text-slate-700 placeholder-slate-400 border-0 focus:border-0 focus:border-transparent ring-0 focus:ring-0 outline-none focus:outline-none appearance-none">
            @if(false)
                <label><input type="checkbox" name="with_trashed" value="1" {{ request('with_trashed') ? 'checked' : '' }}> with trashed</label>
                <label><input type="checkbox" name="only_trashed" value="1" {{ request('only_trashed') ? 'checked' : '' }}> only trashed</label>
            @endif
            <button type="submit" class="ml-3 text-sm font-medium text-slate-600 hover:text-slate-900">Filter</button>
        </div>
    </form>

    {{-- Create --}}
    <a href="{{ route('cms.posts.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Create</a>
</div>

{{-- CARD: Table --}}
<div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <table border="1" cellpadding="6" class="min-w-full text-left text-slate-700 text-sm">
        <thead class="bg-slate-50 text-slate-800 text-sm font-semibold border-b border-slate-200">
            <tr>
                <th class="py-3 px-4">ID</th>
                @foreach ((new \App\Models\Post)->getFillable() as $col)
                <th class="py-3 px-4 capitalize">{{ str_replace('_', ' ', $col) }}</th>
                @endforeach
                <th class="py-3 px-4 w-[220px]">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach ($posts as $item)
                <tr class="bg-white">
                    <td class="py-4 px-4 text-slate-900 font-medium">{{ $item->id }}</td>
                        @foreach ((new \App\Models\Post)->getFillable() as $col)
                            <td class="py-4 px-4 align-top">
                                <div class="max-w-[360px] leading-relaxed">
                                    {{ $item->{$col} }}
                                </div>
                            </td>
                        @endforeach
                    <td class="py-4 px-4">
                        <a href="{{ route('cms.posts.show', $item) }}" class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-slate-700 text-sm font-medium shadow-sm hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-400">Show</a>
                        <a href="{{ route('cms.posts.edit', $item) }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Edit</a>
                        <form action="{{ route('cms.posts.destroy', $item) }}" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Delete?')" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-white text-sm font-medium shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete</button>
                        </form>
                        @if (method_exists($item, 'trashed') && $item->trashed())
                        <form method="POST" action="{{ route('cms.posts.restore', $item->id) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-white text-sm font-medium shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">Restore</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-t border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
    {{ $posts->links() }}
</div>
@endsection
