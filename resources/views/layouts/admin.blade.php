<!DOCTYPE html>
<html lang="en" data-turbo="false">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-lifetime-minutes" content="{{ (int) config('session.lifetime') }}">
    <title>Dashboard Admin - PT 4Putra Vertex Aviary</title>
    <link rel="icon" href="{{ asset('img/4Putraico.png') }}" type="image/png">

    {{-- Preconnect ke external resources --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://res.cloudinary.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/chat.js'])

    {{-- FOUC prevention: cek localStorage sebelum render --}}
    <script>
        (function() {
            var theme = localStorage.getItem('admin_theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- Shared toast notification system --}}
    <script src="{{ asset('js/toast.js') }}" defer></script>

    {{-- Session timeout auto-logout --}}
    <script src="{{ asset('js/session-timeout.js') }}" defer></script>

    {{-- Client-side table search --}}
    <script src="{{ asset('js/table-search.js') }}" defer></script>

    {{-- Refresh daftar tanpa reload penuh setelah CRUD --}}
    <script src="{{ asset('js/admin-refresh.js') }}" defer></script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-300">
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

        <x-admin.sidebar />

        <div class="flex flex-col flex-1 w-full">
            <header class="z-10 py-3 px-4 bg-white shadow-md dark:bg-gray-800 lg:py-4 lg:px-6">
                <div class="flex items-center justify-between h-full">
                    {{-- Hamburger mobile --}}
                    <button @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 -ml-2 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{-- Logo mobile (tampil di header saat sidebar hidden) --}}
                    <a href="{{ route('admin.dashboard') }}" class="lg:hidden font-extrabold text-lg tracking-wider text-gray-900 dark:text-white">
                        4PUTRA<span class="text-[#E62C37]">.</span>
                    </a>

                    <div class="flex flex-1"></div>
                    <ul class="flex items-center flex-shrink-0 space-x-4 lg:space-x-6">
                        <li class="flex">
                            {{-- Dark Mode Toggle (Preline-style, sama dengan public site) --}}
                            <div class="flex items-center">
                                <button type="button" class="hs-dark-mode-active:hidden block hs-dark-mode text-gray-500 dark:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition-colors" data-hs-theme-click-value="dark">
                                    <span class="group inline-flex shrink-0 justify-center items-center size-9">
                                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                                    </span>
                                </button>
                                <button type="button" class="hs-dark-mode-active:block hidden hs-dark-mode text-gray-500 dark:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition-colors" data-hs-theme-click-value="light">
                                    <span class="group inline-flex shrink-0 justify-center items-center size-9">
                                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                                    </span>
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </header>

            <main class="h-full overflow-y-auto bg-gray-50 dark:bg-gray-900">
                <div class="container px-4 py-4 mx-auto lg:px-6 lg:py-0 lg:grid">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <x-admin.toast />

    <x-admin.chat-widget :user="Auth::user()" />

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showToast('success', 'Berhasil', '{{ session('success') }}');
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showToast('error', 'Gagal', '{{ session('error') }}');
            });
        </script>
    @endif

    {{-- Dark Mode Toggle Handler (Preline-style, pure JS) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var html = document.documentElement;
            var darkModeBtns = document.querySelectorAll('.hs-dark-mode');

            function updateDarkModeBtns() {
                var isDark = html.classList.contains('dark');
                darkModeBtns.forEach(function(btn) {
                    var val = btn.getAttribute('data-hs-theme-click-value');
                    if (val === 'dark') {
                        btn.style.display = isDark ? 'none' : 'block';
                    } else {
                        btn.style.display = isDark ? 'block' : 'none';
                    }
                });
            }

            function setTheme(theme) {
                if (theme === 'dark') {
                    html.classList.add('dark');
                    localStorage.setItem('admin_theme', 'dark');
                } else {
                    html.classList.remove('dark');
                    localStorage.setItem('admin_theme', 'light');
                }
                updateDarkModeBtns();
            }

            darkModeBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var theme = this.getAttribute('data-hs-theme-click-value');
                    setTheme(theme);
                });
            });

            updateDarkModeBtns();
        });
    </script>
</body>

</html>