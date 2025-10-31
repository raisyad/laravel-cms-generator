@extends('layouts.cms')

@section('title', 'CMS Dashboard')

@section('content')
  <h1 class="text-2xl font-semibold mb-6">CMS Dashboard</h1>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="rounded-2xl ring-1 p-6 bg-white text-slate-900 ring-slate-200
            dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">
      <div class="text-sm dark:text-slate-400">Quick Links</div>
      <div class="mt-3 flex flex-wrap gap-2">
        <a href="{{ route('cms.dashboard') }}"
           class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-white text-sm hover:bg-indigo-700">
          Home
        </a>
        {{-- contoh modul (akan ada setelah generator membuatnya) --}}
        @if(Route::has('cms.posts.index'))
          <a href="{{ route('cms.posts.index') }}"
             class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-1.5 text-white text-sm hover:dark:bg-slate-900">
            Posts
          </a>
        @endif
      </div>
    </div>

    <div class="rounded-2xl ring-1 p-6 bg-white text-slate-900 ring-slate-200
            dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">
      <div class="text-sm dark:text-slate-400">Status</div>
      <p class="mt-3 dark:text-slate-300">You’re logged in as <b>{{ auth()->user()->name }}</b>.</p>
    </div>

    <div class="rounded-2xl ring-1 p-6 bg-white text-slate-900 ring-slate-200
            dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">
      <div class="text-sm dark:text-slate-400">Getting Started</div>
      <ul class="mt-3 list-disc pl-5 dark:text-slate-300 space-y-1">
        <li>Generate module: <code>php artisan make:cms posts --with-search --with-softdeletes --with-policy</code></li>
        <li>Temukan modul di menu atas (link otomatis jika route ada).</li>
      </ul>
    </div>
  </div>
@endsection
