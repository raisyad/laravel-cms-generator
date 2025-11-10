@extends('layouts.cms')

@section('title', 'CMS Dashboard')

@section('content')
  <div class="space-y-6">
    {{-- Quick Links Card --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-lg">
      <h2 class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-4">Quick Links</h2>
      <div class="flex flex-wrap gap-3">
        <a href="{{ route('cms.dashboard') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
          Home
        </a>
        @if(Route::has('cms.posts.index'))
          <a href="{{ route('cms.posts.index') }}"
             class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors border border-slate-300 dark:border-slate-700">
            Posts
          </a>
        @endif
      </div>
    </div>

    {{-- Status Card --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-lg">
      <h2 class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-4">Status</h2>
      <p class="text-slate-700 dark:text-slate-300">
        You're logged in as <span class="font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</span>.
      </p>
    </div>

    {{-- Getting Started Card --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-slate-200 dark:border-slate-800 shadow-lg">
      <h2 class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-4">Getting Started</h2>
      <ul class="space-y-2 text-slate-700 dark:text-slate-300">
        <li class="flex items-start gap-2">
          <span class="text-slate-500 dark:text-slate-500 mt-1">•</span>
          <span>
            Generate module: <code class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-xs text-slate-800 dark:text-slate-200">php artisan make:cms posts --with-search --with-softdeletes --with-policy</code>
          </span>
        </li>
        <li class="flex items-start gap-2">
          <span class="text-slate-500 mt-1">•</span>
          <span>Find modules in the top menu (links appear automatically if routes exist).</span>
        </li>
        <li class="flex items-start gap-2">
          <span class="text-slate-500 mt-1">•</span>
          <span>Customize your CMS by editing the layout and adding new modules.</span>
        </li>
      </ul>
    </div>
  </div>
@endsection
