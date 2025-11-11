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
        :root {
            --sidebar-expanded-width: 240px;
            --sidebar-collapsed-width: 72px;
        }
    </style>
</head>
<body class="min-h-screen antialiased bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 overflow-x-hidden" 
      x-data="{
          sidebarOpen: false,
          sidebarCollapsed: false,
          init() {
              // Load sidebar collapsed state from localStorage (desktop only)
              if (window.innerWidth >= 768) {
                  const stored = localStorage.getItem('cms-sidebar-collapsed');
                  if (stored === 'true') {
                      this.sidebarCollapsed = true;
                  }
              }
          }
      }"
      x-init="init()"
      :class="{ 'sidebar-collapsed': sidebarCollapsed && window.innerWidth >= 768 }">
    @php
        $navigationItems = \App\Support\CmsNavigation::items();
        $currentSectionLabel = \App\Support\CmsNavigation::currentSectionLabel();
        $currentSectionUrl = \App\Support\CmsNavigation::currentSectionUrl();
        $currentSectionKey = \App\Support\CmsNavigation::currentSectionKey();
        $user = Auth::user();
        $email = $user?->email;
        $displayName = $email ? explode('@', $email)[0] : 'User';
        $isDashboard = $currentSectionKey === 'dashboard';
    @endphp
    <div class="flex min-h-screen overflow-x-hidden">
        {{-- Unified Sidebar (desktop) - Single column with header and menu --}}
        <aside class="hidden md:flex fixed left-0 top-0 h-screen bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex-col z-30 transition-all duration-300 ease-in-out overflow-x-hidden"
               :class="sidebarCollapsed ? 'w-[72px]' : 'w-[240px]'">
            
            {{-- Sidebar Header: CMS brand + hamburger in one row --}}
            <div class="h-16 flex items-center border-b border-slate-200 dark:border-slate-800 flex-shrink-0 overflow-x-hidden"
                 :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4'">
                {{-- Brand: Full "CMS" when expanded, hidden when collapsed --}}
                <div x-show="!sidebarCollapsed" class="flex-shrink-0">
                    @if($currentSectionUrl)
                        <a href="{{ $currentSectionUrl }}" 
                           class="font-bold text-xl text-slate-900 dark:text-white hover:text-slate-700 dark:hover:text-slate-300 transition-colors whitespace-nowrap">
                            CMS
                        </a>
                    @else
                        <span class="font-bold text-xl text-slate-900 dark:text-white whitespace-nowrap">
                            CMS
                        </span>
                    @endif
                </div>
                {{-- Single Hamburger button - handles desktop collapse --}}
                <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('cms-sidebar-collapsed', sidebarCollapsed);"
                        class="p-1.5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors flex-shrink-0"
                        :class="sidebarCollapsed ? '' : 'ml-auto'"
                        title="Toggle sidebar"
                        aria-label="Toggle sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            {{-- Navigation Links --}}
            <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 space-y-1"
                 :class="sidebarCollapsed ? 'px-2' : 'px-4'">
                @foreach($navigationItems as $item)
                    @php
                        $icon = !empty($item['icon']) ? $item['icon'] : 'home';
                    @endphp
                    <a href="{{ $item['url'] }}"
                       @class([
                           'flex items-center rounded-lg text-sm font-medium transition-all duration-200 relative group overflow-x-hidden',
                           'bg-slate-200 dark:bg-slate-800 text-slate-900 dark:text-white' => $item['active'],
                           'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' => ! $item['active'],
                       ])
                       :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'gap-2 px-3 py-2'"
                       aria-current="{{ $item['active'] ? 'page' : 'false' }}">
                        <i data-lucide="{{ $icon }}" class="flex-shrink-0" :class="sidebarCollapsed ? 'w-5 h-5' : 'w-4 h-4'"></i>
                        <span x-show="!sidebarCollapsed" class="block truncate">{{ $item['label'] }}</span>
                        {{-- Enhanced tooltip for collapsed state with smooth animation --}}
                        <span x-show="sidebarCollapsed" 
                              x-transition:enter="transition ease-out duration-200"
                              x-transition:enter-start="opacity-0 translate-x-0"
                              x-transition:enter-end="opacity-100 translate-x-1"
                              x-transition:leave="transition ease-in duration-150"
                              x-transition:leave-start="opacity-100 translate-x-1"
                              x-transition:leave-end="opacity-0 translate-x-0"
                              class="absolute left-full ml-3 px-3 py-1.5 bg-slate-900 dark:bg-slate-700 text-white text-xs font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-50 shadow-lg"
                              x-cloak>
                            {{ $item['label'] }}
                            {{-- Tooltip arrow --}}
                            <span class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-700"></span>
                        </span>
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- Mobile Hamburger Button (fixed top-left, only on mobile) --}}
        <button x-show="!sidebarOpen"
                @click="sidebarOpen = true"
                class="md:hidden fixed left-4 top-4 z-40 p-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm"
                title="Open sidebar"
                aria-label="Open sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col transition-all duration-300 ease-in-out min-w-0"
             :class="sidebarCollapsed && window.innerWidth >= 768 ? 'md:ml-[72px]' : 'md:ml-[240px]'">
            {{-- Top Navbar --}}
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 sticky top-0 z-20">
                {{-- Left: Current Section pill (hamburger is in sidebar header) --}}
                <div class="flex items-center gap-4">
                    @if($currentSectionUrl)
                        <a href="{{ $currentSectionUrl }}"
                           class="px-3 py-1.5 rounded-lg text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            {{ $currentSectionLabel }}
                        </a>
                    @else
                        <span class="px-3 py-1.5 rounded-lg text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                            {{ $currentSectionLabel }}
                        </span>
                    @endif
                </div>

                {{-- Right Side: User Name (non-dashboard), Theme Toggle and Logout --}}
                <div class="flex items-center gap-3">
                    @if(!$isDashboard)
                        {{-- User Name (only for non-dashboard pages) --}}
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ $displayName }}
                        </span>
                    @endif

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
            <main class="flex-1 overflow-y-auto overflow-x-hidden p-6">
                @if($isDashboard)
                    {{-- User Name (only for dashboard) --}}
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-6">
                        {{ $displayName }}
                    </h1>
                @endif
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
            <aside class="fixed left-0 top-0 h-full w-60 bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col overflow-x-hidden">
                <div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800">
                    @if($currentSectionUrl)
                        <a href="{{ $currentSectionUrl }}" class="text-xl font-bold text-slate-900 dark:text-white">CMS</a>
                    @else
                        <span class="text-xl font-bold text-slate-900 dark:text-white">CMS</span>
                    @endif
                    <button @click="sidebarOpen = false" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-4 space-y-1">
                    @foreach($navigationItems as $item)
                        @php
                            $icon = !empty($item['icon']) ? $item['icon'] : 'home';
                        @endphp
                        <a href="{{ $item['url'] }}"
                           @class([
                               'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                               'bg-slate-200 dark:bg-slate-800 text-slate-900 dark:text-white' => $item['active'],
                               'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' => ! $item['active'],
                           ])
                           @click="sidebarOpen = false">
                            <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
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

    {{-- Sidebar Resize Handler --}}
    <script>
        /**
         * Handle window resize to reset sidebar state appropriately
         * This ensures the sidebar behaves correctly when switching between mobile and desktop views
         */
        (function() {
            function handleResize() {
                // On mobile, sidebar uses overlay mode, so collapse state doesn't apply
                // The Alpine.js reactive system will handle the UI updates automatically
                // This handler is mainly for edge cases
                if (window.innerWidth < 768) {
                    // Mobile view: sidebar collapse state is not relevant (overlay mode)
                    // Alpine will handle the UI updates via x-show directives
                }
            }
            
            // Debounce resize handler to avoid excessive updates
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(handleResize, 150);
            });
        })();
    </script>
</body>
</html>
