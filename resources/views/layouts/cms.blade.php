<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <script>
  // anti-flicker: pasang seawal mungkin
  (function() {
    const stored = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const shouldDark = stored ? stored === 'dark' : prefersDark;
    if (shouldDark) document.documentElement.classList.add('dark');
  })();
  </script>
</head>
<body class="min-h-screen antialiased">
    <div class="min-h-screen">
        {{-- Pakai navbar Breeze biar konsisten --}}
        @include('layouts.partials.cms-nav')

        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          @yield('content')
        </main>
  </div>
</body>
</html>
