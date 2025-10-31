@props(['title' => null, 'subtitle' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen h-full bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] flex p-6 lg:p-8 items-center lg:justify-center">
    {{-- Header / Nav --}}
    <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6">
        <nav class="flex items-center justify-between gap-4">
            <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 font-medium">
                <x-application-logo class="h-6 w-6" />
                <span class="hidden sm:inline">{{ config('app.name') }}</span>
            </a>

            <div class="flex items-center gap-2">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('cms.dashboard') }}"
                           class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm hover:border-black dark:hover:border-white">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-1.5 rounded-sm hover:underline">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-block px-5 py-1.5 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm hover:border-black dark:hover:border-white">
                                Register
                            </a>
                        @endif
                    @endauth
                @endif

                {{-- Theme toggle --}}
                <button type="button" id="themeToggle"
                        class="px-3 py-1.5 rounded-sm border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A]"
                        aria-label="Toggle theme">
                    <span class="dark:hidden">🌙</span>
                    <span class="hidden dark:inline">☀️</span>
                </button>
            </div>
        </nav>
    </header>

    {{-- Content --}}
    <div class="flex items-center justify-center w-full lg:grow">
        <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
            {{-- Left: card for form --}}
            <section class="flex-1 p-6 pb-12 lg:p-20 bg-white dark:bg-[#161615]
                            shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0_0_0_1px_#fffaed2d]
                            rounded-bl-lg rounded-br-lg lg:rounded-tl-lg lg:rounded-br-none">
                @if($title)
                    <h1 class="text-xl font-semibold mb-2">{{ $title }}</h1>
                @endif
                @if($subtitle)
                    <p class="mb-6 text-[#706f6c] dark:text-[#A1A09A]">{{ $subtitle }}</p>
                @endif

                {{-- slot = form halaman (login/register/forgot/etc) --}}
                <div class="max-w-md">
                    {{ $slot }}
                </div>
            </section>

            {{-- Right: visual/hero (boleh ganti SVG yang di welcome.blade) --}}
            <aside class="bg-[#fff2f2] dark:bg-[#1D0002] relative lg:-ml-px -mb-px lg:mb-0
                           rounded-t-lg lg:rounded-t-none lg:rounded-r-lg aspect-[335/376] lg:aspect-auto
                           w-full lg:w-[438px] shrink-0 overflow-hidden">
                {{-- Contoh: tulisan Laravel (ringkas). Anda bisa copy SVG lengkap dari welcome.blade --}}
                <div class="absolute inset-0 grid place-items-center">
                    <div class="text-[#F53003] dark:text-[#F61500] text-5xl font-black tracking-tight select-none">
                        Laravel
                    </div>
                </div>
                <div class="absolute inset-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg
                            shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                </div>
            </aside>
        </main>
    </div>

    <script>
        // Theme: load from localStorage or system, then allow toggle
        const root = document.documentElement;
        const apply = (mode) => {
            if (mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
        };
        const saved = localStorage.getItem('theme') || 'system';
        apply(saved);
        document.getElementById('themeToggle')?.addEventListener('click', () => {
            const now = root.classList.contains('dark') ? 'light' : 'dark';
            localStorage.setItem('theme', now);
            apply(now);
        });
        // react to system changes if user chose "system"
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            const curr = localStorage.getItem('theme') || 'system';
            if (curr === 'system') apply('system');
        });
    </script>
</body>
</html>
