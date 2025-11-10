<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CMS Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    {{-- Theme initialization: apply as early as possible to prevent flicker --}}
    <script>
        /**
         * CMS Theme System - Single Source of Truth
         * 
         * Rules:
         * - Dark mode: <html> element has class "dark"
         * - Light mode: <html> element does NOT have class "dark"
         * - localStorage key: "cms-theme" (values: "dark" or "light")
         * - On page load: read from localStorage, or use system preference if not set
         * - On toggle: check current state from <html> class, toggle, save to localStorage, update icon
         */
        (function() {
            const THEME_STORAGE_KEY = 'cms-theme';
            const root = document.documentElement;
            
            // Read stored theme or detect system preference
            const stored = localStorage.getItem(THEME_STORAGE_KEY);
            let initialTheme;
            
            if (stored === 'dark' || stored === 'light') {
                initialTheme = stored;
            } else {
                // No stored value: use system preference
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                initialTheme = prefersDark ? 'dark' : 'light';
            }
            
            // Apply theme immediately (before DOM ready to prevent flicker)
            if (initialTheme === 'dark') {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
        })();
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen antialiased bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Fixed Left Sidebar --}}
        <aside class="hidden md:flex fixed left-0 top-0 h-screen w-60 bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex-col z-30">
            {{-- App Name --}}
            <div class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-800">
                <a href="{{ route('cms.dashboard') }}" class="text-xl font-bold text-slate-900 dark:text-white hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
                    CMS
                </a>
            </div>

            {{-- Navigation Links --}}
            <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
                @php
                    $items = config('cms.resources', []);
                @endphp
                @foreach($items as $key => $resource)
                    @if($resource['visible'] ?? true)
                        @php
                            $routeName = $key === 'dashboard' ? 'cms.dashboard' : "cms.$key.index";
                            $active = request()->routeIs("cms.$key*");
                            $label = $resource['label'] ?? \Illuminate\Support\Str::headline($key);
                            $icon = $resource['icon'] ?? null;
                        @endphp
                        <a href="{{ route($routeName) }}"
                           @class([
                               'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                               'bg-slate-200 dark:bg-slate-800 text-slate-900 dark:text-white' => $active,
                               'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' => !$active,
                           ])
                           aria-current="{{ $active ? 'page' : 'false' }}">
                            @if(!empty($icon))
                                <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                            @endif
                            <span>{{ $label }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col md:ml-60">
            {{-- Top Navbar --}}
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 sticky top-0 z-20">
                {{-- Left: Mobile menu button and Dashboard link --}}
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="md:hidden p-2 -ml-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <a href="{{ route('cms.dashboard') }}"
                       class="px-3 py-1.5 rounded-lg text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        Dashboard
                    </a>
                </div>

                {{-- Right Side: Theme Toggle and Logout --}}
                <div class="flex items-center gap-3">
                    {{-- Theme Toggle Button (Single Source of Truth) --}}
                    <button id="theme-toggle"
                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-slate-300 dark:focus:ring-slate-700"
                            title="Toggle theme"
                            aria-label="Toggle theme">
                        <i id="theme-toggle-icon" class="w-4 h-4"></i>
                    </button>

                    {{-- Log Out Button --}}
                    <form method="POST" action="{{ route('logout') }}" class="contents">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 rounded-lg text-sm font-semibold bg-red-600 text-white hover:bg-red-500 transition-colors">
                            Log Out
                        </button>
                    </form>
                </div>
            </header>

            {{-- Content Area --}}
            <main class="flex-1 overflow-y-auto p-6">
                @php
                    $user = Auth::user();
                    $email = $user?->email;
                    $displayName = $email ? explode('@', $email)[0] : 'User';
                @endphp
                <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-6">
                    {{ $displayName }}
                </h1>
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Mobile Sidebar Overlay (for responsive) --}}
    <div class="md:hidden">
        <div x-show="sidebarOpen"
             @click.away="sidebarOpen = false"
             x-cloak
             class="fixed inset-0 z-50 md:hidden">
            <div class="fixed inset-0 bg-black/50" @click="sidebarOpen = false"></div>
            <aside class="fixed left-0 top-0 h-full w-60 bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col">
                <div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800">
                    <a href="{{ route('cms.dashboard') }}" class="text-xl font-bold text-slate-900 dark:text-white">CMS</a>
                    <button @click="sidebarOpen = false" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
                    @foreach($items as $key => $resource)
                        @if($resource['visible'] ?? true)
                            @php
                                $routeName = $key === 'dashboard' ? 'cms.dashboard' : "cms.$key.index";
                                $active = request()->routeIs("cms.$key*");
                                $label = $resource['label'] ?? \Illuminate\Support\Str::headline($key);
                                $icon = $resource['icon'] ?? null;
                            @endphp
                            <a href="{{ route($routeName) }}"
                               @class([
                                   'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                   'bg-slate-200 dark:bg-slate-800 text-slate-900 dark:text-white' => $active,
                                   'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' => !$active,
                               ])
                               @click="sidebarOpen = false">
                                @if(!empty($icon))
                                    <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                                @endif
                                <span>{{ $label }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            </aside>
        </div>
    </div>

    {{-- Theme Toggle Script - Single Source of Truth for CMS Theme System --}}
    <script>
        /**
         * CMS Theme Toggle - Centralized Implementation
         * 
         * This is the ONLY theme toggle implementation for CMS pages.
         * All CMS pages extend layouts.cms and inherit this functionality.
         */
        (function() {
            const THEME_STORAGE_KEY = 'cms-theme';
            const root = document.documentElement;
            
            /**
             * Update the theme toggle icon based on current theme state
             * @param {HTMLElement} iconEl - The icon element
             * @param {boolean} isDark - Whether dark mode is active
             */
            function updateThemeIcon(iconEl, isDark) {
                if (!iconEl) return;
                
                // Remove existing SVG if any
                const existingSvg = iconEl.querySelector('svg');
                if (existingSvg) {
                    existingSvg.remove();
                }
                
                // Set the correct icon name
                // Dark mode = moon icon, Light mode = sun icon
                iconEl.setAttribute('data-lucide', isDark ? 'moon' : 'sun');
                
                // Recreate the icon using Lucide
                if (window.lucide) {
                    lucide.createIcons();
                }
            }
            
            /**
             * Apply theme to the document
             * @param {string} theme - 'dark' or 'light'
             */
            function applyTheme(theme) {
                const isDark = theme === 'dark';
                
                // Update <html> element class (source of truth)
                if (isDark) {
                    root.classList.add('dark');
                } else {
                    root.classList.remove('dark');
                }
                
                // Update icon
                const icon = document.getElementById('theme-toggle-icon');
                if (icon) {
                    updateThemeIcon(icon, isDark);
                }
            }
            
            /**
             * Initialize theme toggle functionality
             * Waits for DOM and Lucide to be ready
             */
            function initializeThemeToggle() {
                // Wait for Lucide to load
                if (!window.lucide) {
                    setTimeout(initializeThemeToggle, 50);
                    return;
                }
                
                // Initialize all Lucide icons (including sidebar icons)
                lucide.createIcons();
                
                // Set initial icon state based on current <html> class
                const icon = document.getElementById('theme-toggle-icon');
                if (icon) {
                    const isDark = root.classList.contains('dark');
                    updateThemeIcon(icon, isDark);
                }
                
                // Attach click handler to theme toggle button
                const btn = document.getElementById('theme-toggle');
                if (btn) {
                    btn.addEventListener('click', function() {
                        // Detect current mode from <html> element class (source of truth)
                        const isDark = root.classList.contains('dark');
                        
                        // Determine next theme
                        const nextTheme = isDark ? 'light' : 'dark';
                        
                        // Save to localStorage
                        localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
                        
                        // Apply the new theme
                        applyTheme(nextTheme);
                    });
                }
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeThemeToggle);
            } else {
                // DOM already ready
                initializeThemeToggle();
            }
        })();
    </script>
</body>
</html>
