@extends('layouts.cms')

@section('content')
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Edit {{ ucfirst('posts') }}</h1>
    <a href="{{ route('cms.posts.index') }}"
       class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
      ← Back
    </a>
  </div>

  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
    <form method="POST" action="{{ route('cms.posts.update', $post) }}" class="space-y-6">
      @csrf
      @method('PUT')

      <div class="grid grid-cols-12 items-start gap-4">
  <label class="col-span-12 sm:col-span-2 pt-2 text-sm font-medium text-slate-700">Title</label>
  <div class="col-span-12 sm:col-span-6">
    <input type="text" name="title" value="{{ old('title', $post->title ?? '') }}" class="mt-1 block w-full rounded-xl border border-transparent ring-1 ring-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
  </div>
</div>
<div class="grid grid-cols-12 items-start gap-4">
  <label class="col-span-12 sm:col-span-2 pt-2 text-sm font-medium text-slate-700">Body</label>
  <div class="col-span-12 sm:col-span-8">
    <textarea name="body" rows="4" class="mt-1 block w-full rounded-xl border border-transparent ring-1 ring-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('body', $post->body ?? '') }}</textarea>
    @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
  </div>
</div>
<div class="grid grid-cols-12 items-start gap-4">
  <label class="col-span-12 sm:col-span-2 pt-2 text-sm font-medium text-slate-700">Published At</label>
  <div class="col-span-12 sm:col-span-6">
    <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at ?? '') }}" class="mt-1 block w-full rounded-xl border border-transparent ring-1 ring-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    @error('published_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
  </div>
</div>

      <div class="flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-3 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Update
        </button>
        <a href="{{ route('cms.posts.index') }}"
           class="inline-flex items-center rounded-xl bg-white px-5 py-3 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">
          Cancel
        </a>
      </div>
    </form>
  </div>
@endsection
