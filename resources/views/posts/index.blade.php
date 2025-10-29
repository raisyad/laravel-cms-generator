@extends('layouts.cms')

@section('content')
    {{--  Title --}}
    <h1 class="text-3xl font-semibold text-slate-800 mb-6">{{ ucfirst('posts') }} - Index</h1>
    <div class="sm:items-start gap-4 mb-8">
        {{-- Search form --}}
        <form method="GET" action="{{ route('cms.posts.index') }}"
            class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">

            <div class="flex items-center bg-white rounded-xl shadow-sm ring-1 ring-slate-200 px-4 py-3 w-full sm:w-[400px]">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..."
                    class="w-full bg-transparent text-slate-700 placeholder-slate-400 focus:outline-none border-none me-3 focus:border-radius-700">
                @if (false)
                    <label><input type="checkbox" name="with_trashed" value="1"
                            {{ request('with_trashed') ? 'checked' : '' }}> with trashed</label>
                    <label><input type="checkbox" name="only_trashed" value="1"
                            {{ request('only_trashed') ? 'checked' : '' }}> only trashed</label>
                @endif
                <button type="submit" class="text-sm font-medium text-slate-600 hover:text-slate-900">Filter</button>
            </div>
        </form>


        {{-- Create button (di bawah di mobile, di kiri di desktop) --}}
        <a href="{{ route('cms.posts.create') }}"
            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mt-5">Create
            New</a>

    </div>


    {{-- Table card --}}
    <div class="bg-white rounde-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <table class="min-w-full text-left text-slate-700">
            <thead class="bg-slate-50 text-slate-800 text-sm font-semibold border-b border-slate-200">
                <tr>
                    <th class="py-3 px-4">ID</th>
                    @foreach ((new \App\Models\Post)->getFillable() as $col)
                        <th class="py-3 px-4 capitalize">{{ str_replace('_', ' ', $col) }}</th>
                    @endforeach
                    <th class="py-3 px-4 w-[140px]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach ($posts as $item)
                    <tr class="bg-white">
                        <td class="py-4 px-4 text-slate-900 font-medium">{{ $item->id }}</td>
                        @foreach ((new \App\Models\Post)->getFillable() as $col)
                            <td class="py-4 px-4 align-top text-slate-700">
                                <div class="max-w-[280px] align-top leading-relaxed">
                                    {{ $item->{$col} }}
                                </div>
                            </td>
                        @endforeach
                        <td class="py-4 px-4 whitespace-nowrap">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('cms.posts.show', $item) }}"
                                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Show</a>
                                <a href="{{ route('cms.posts.edit', $item) }}"
                                    class="inline-flex items-center rounded-lg bg-yellow-600 px-3 py-1.5 text-white text-sm font-medium shadow-sm hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">Edit</a>
                                <form action="{{ route('cms.posts.destroy', $item) }}" method="POST"
                                    style="display:inline">
                                    @csrf @method('DELETE')
                                    <button
                                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-white text-sm font-medium shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        type="submit" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                                @if (method_exists($item, 'trashed') && $item->trashed())
                                    <form method="POST" action="{{ route('cms.posts.restore', $item->id) }}"
                                        style="display:inline">
                                        @csrf
                                        <button
                                            class="inline-flex items-center rounded-lg bg-grey-600 px-3 py-1.5 text-white text-sm font-medium shadow-sm hover:bg-grey-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-grey-500"
                                            type="submit">Restore</button>
                                    </form>
                                @endif
                            </div>
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
