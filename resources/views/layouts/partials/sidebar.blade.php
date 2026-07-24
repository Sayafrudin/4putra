<aside
    class="z-20 hidden w-64 overflow-y-auto bg-white dark:bg-gray-800 md:block flex-shrink-0 border-r border-gray-100 dark:border-gray-700">
    <div class="py-4 text-gray-500 dark:text-gray-400">
        <a class="ml-6 text-xl font-extrabold tracking-wider text-gray-900 dark:text-white"
            href="{{ route('admin.dashboard') }}">
            4PUTRA<span class="text-[#E62C37]">.</span>
        </a>

        <ul class="mt-6" x-data="{ openMenu: {{ request()->routeIs('admin.achievements.*') || request()->routeIs('admin.collections.*') ? 'true' : 'false' }} }">
            <!-- Menu Dashboard Utama -->
            <li class="relative px-6 py-3">
                @if (request()->routeIs('admin.dashboard'))
                    <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg"
                        aria-hidden="true"></span>
                @endif
                <a class="inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.dashboard') ? 'text-gray-900 dark:text-white font-bold' : '' }}"
                    href="{{ route('admin.dashboard') }}">
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 00-1-1m-5 0a1 1 0 00-1 1v4a1 1 0 001 1m6 0v-4a1 1 0 00-1-1H9a1 1 0 00-1 1v4a1 1 0 001 1m6 0h6">
                        </path>
                    </svg>
                    <span class="ml-4">Dashboard Utama</span>
                </a>
            </li>

            <!-- Dropdown: Manajemen Konten -->
            <li class="relative px-6 py-3">
                @if (request()->routeIs('admin.achievements.*') || request()->routeIs('admin.collections.*'))
                    <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg"
                        aria-hidden="true"></span>
                @endif
                <button @click="openMenu = !openMenu"
                    class="inline-flex items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.achievements.*') || request()->routeIs('admin.collections.*') ? 'text-gray-900 dark:text-white font-bold' : '' }}">
                    <span class="inline-flex items-center">
                        <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                        <span class="ml-4">Manajemen Konten</span>
                    </span>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openMenu }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openMenu" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1" class="mt-2 ml-4 space-y-1">
                    <a href="{{ route('admin.achievements.index') }}"
                        class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.achievements.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Achievements
                    </a>
                    <a href="{{ route('admin.collections.index') }}"
                        class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.collections.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Koleksi
                    </a>
                </div>
            </li>
        </ul>
    </div>
</aside>
