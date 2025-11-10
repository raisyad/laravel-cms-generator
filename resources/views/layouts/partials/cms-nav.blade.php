{{-- resources/views/layouts/partials/cms-nav.blade.php --}}
{{-- resources/views/layouts/partials/cms-nav.blade.php --}}
@php $items = config('cms.resources', []); @endphp

<header class="bg-white/70 backdrop-blur ring-1 ring-slate-200 dark:bg-slate-900/70 dark:ring-slate-700">
  <div class="container px-4 py-3 flex items-center justify-between">
    <a href="{{ route('cms.dashboard') }}" class="font-semibold">CMS</a>
    <nav class="hidden md:flex items-center gap-1">
      <a href="{{ route('cms.dashboard') }}"
         @class([
           'px-3 py-1.5 rounded-lg text-sm font-medium',
           'bg-slate-100 text-slate-900' => request()->routeIs('cms.dashboard'),
           'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100' => !request()->routeIs('cms.dashboard'),
         ])
         aria-current="{{ request()->routeIs('cms.dashboard') ? 'page' : 'false' }}">
        Dashboard
      </a>

      @foreach($items as $key => $meta)
        @continue(isset($meta['visible']) && !$meta['visible'])
        @php
          $active = request()->routeIs("cms.$key.*");
          $label  = $meta['label'] ?? \Illuminate\Support\Str::headline($key);
        @endphp
        <a href="{{ route("cms.$key.index") }}"
           @class([
             'px-3 py-1.5 rounded-lg text-sm font-medium',
             'bg-slate-100 text-slate-900' => $active,
             'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100' => !$active,
           ])
           aria-current="{{ $active ? 'page' : 'false' }}">
          {{ $label }}
        </a>
      @endforeach

      <!-- Authentication -->
    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <x-responsive-nav-link :href="route('logout')"
                onclick="event.preventDefault();
                            this.closest('form').submit();">
            {{ __('Log Out') }}
        </x-responsive-nav-link>
    </form>

      {{-- Theme toggle removed: use the centralized implementation in layouts/cms.blade.php --}}
      {{-- This partial is deprecated. Theme toggle is now handled in the main CMS layout. --}}
    </nav>
  </div>
</header>
