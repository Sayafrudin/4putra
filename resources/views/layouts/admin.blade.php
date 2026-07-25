<!DOCTYPE html>
<html :class="{ 'dark': dark }" x-data="data()" lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Admin - PT 4Putra Vertex Aviary</title>
    <link rel="icon" href="{{ asset('img/4Putraico.png') }}" type="image/png">

    {{-- Preconnect ke external resources --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Shared toast notification system --}}
    <script src="{{ asset('js/toast.js') }}" defer></script>

    {{-- Session timeout auto-logout --}}
    <script src="{{ asset('js/session-timeout.js') }}" defer></script>

    {{-- Client-side table search --}}
    <script src="{{ asset('js/table-search.js') }}" defer></script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <div class="flex h-screen overflow-hidden">
        <x-admin.sidebar />

        <div class="flex flex-col flex-1 w-full">
            <header class="z-10 py-4 bg-white shadow-md dark:bg-gray-800">
                <div class="container flex items-center justify-between h-full px-6 mx-auto text-purple-600 dark:text-purple-300">
                    <div class="flex flex-1"></div>
                    <ul class="flex items-center flex-shrink-0 space-x-6">
                        <li class="flex">
                            <button class="rounded-md focus:outline-none focus:shadow-outline-purple text-gray-500 dark:text-gray-400"
                                @click="toggleTheme" aria-label="Toggle color mode">
                                <template x-if="!dark">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                                    </svg>
                                </template>
                                <template x-if="dark">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 01-1.414-0l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 17.95a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414zm-2.12-10.607a1 1 0 010-1.414l.707-.707a1 1 0 111.414 1.414l-.707.707a1 1 0 01-1.414 0zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                            </button>
                        </li>
                    </ul>
                </div>
            </header>

            <main class="h-full overflow-y-auto bg-gray-50 dark:bg-gray-900">
                <div class="container px-6 mx-auto grid">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <x-admin.toast />

    <x-admin.chat-widget :user="Auth::user()" />

    {{-- Chat JS loaded lazily (tidak memblok render halaman) --}}
    <script type="module" src="{{ asset('build/assets/chat-CZ2_Y-v4.js') }}" defer></script>

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
</body>

</html>