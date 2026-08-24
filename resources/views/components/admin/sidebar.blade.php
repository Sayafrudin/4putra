@props(['activeRoute' => ''])

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="z-40 fixed inset-y-0 left-0 w-64 overflow-y-auto bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:z-20 flex-shrink-0">
    <div class="flex flex-col h-full py-4 text-gray-500 dark:text-gray-400">
        <div>
            {{-- Close button mobile --}}
            <div class="flex items-center justify-between px-6 mb-4 lg:mb-0">
                <a class="text-xl font-extrabold tracking-wider text-gray-900 dark:text-white"
                    href="{{ route('admin.dashboard') }}">
                    4PUTRA<span class="text-[#E62C37]">.</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <ul class="mt-6" x-data="{ openMenu: {{ request()->routeIs('admin.achievements.*') || request()->routeIs('admin.collections.*') || request()->routeIs('admin.daily-activities.*') ? 'true' : 'false' }} }">
                <li class="relative px-6 py-3">
                    @if (request()->routeIs('admin.dashboard'))
                        <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
                    @endif
                    <a class="inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.dashboard') ? 'text-gray-900 dark:text-white font-bold' : '' }}"
                        href="{{ route('admin.dashboard') }}" @click="sidebarOpen = false">
                        <svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 00-1-1m-5 0a1 1 0 00-1 1v4a1 1 0 001 1m6 0v-4a1 1 0 00-1-1H9a1 1 0 00-1 1v4a1 1 0 001 1m6 0h6" />
                        </svg>
                        <span class="ml-4">Beranda (Dashboard)</span>
                    </a>
                </li>

                <li class="relative px-6 py-3">
                    @if (request()->routeIs('admin.achievements.*') || request()->routeIs('admin.collections.*') || request()->routeIs('admin.daily-activities.*'))
                        <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
                    @endif
                    <button @click="openMenu = !openMenu"
                        class="inline-flex items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.achievements.*') || request()->routeIs('admin.collections.*') || request()->routeIs('admin.daily-activities.*') ? 'text-gray-900 dark:text-white font-bold' : '' }}">
                        <span class="inline-flex items-center">
                            <svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span class="ml-4">Manajemen Konten</span>
                        </span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="openMenu" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="mt-2 ml-4 space-y-1">
                        <a href="{{ route('admin.achievements.index') }}"
                            @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.achievements.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Pencapaian (Achievements)
                        </a>
                        <a href="{{ route('admin.collections.index') }}"
                            @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.collections.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Koleksi (Collections)
                        </a>
                        <a href="{{ route('admin.daily-activities.index') }}"
                            @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.daily-activities.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            Aktivitas Harian (Daily Activities)
                        </a>
                    </div>
                </li>

                {{-- Menu Manajemen User (hanya admin) --}}
                @if (Auth::user()->isAdmin())
                    <li class="relative px-6 py-3">
                        @if (request()->routeIs('admin.users.*'))
                            <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
                        @endif
                    <a class="inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.users.*') ? 'text-gray-900 dark:text-white font-bold' : '' }}"
                        href="{{ route('admin.users.index') }}" @click="sidebarOpen = false">
                            <svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zM12 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="ml-4">Kelola Akun User</span>
                        </a>
                    </li>
                @endif

                {{-- Menu Chatbot WhatsApp (semua user) --}}
                <li class="relative px-6 py-3" x-data="{ openChatbot: {{ request()->routeIs('admin.chatbot.*') ? 'true' : 'false' }} }">
                    @if (request()->routeIs('admin.chatbot.*'))
                        <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg" aria-hidden="true"></span>
                    @endif
                    <button @click="openChatbot = !openChatbot"
                        class="inline-flex items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.chatbot.*') ? 'text-gray-900 dark:text-white font-bold' : '' }}">
                        <span class="inline-flex items-center">
                            <svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                            </svg>
                            <span class="ml-4">Chatbot WhatsApp</span>
                        </span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openChatbot }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="openChatbot" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                        class="mt-2 ml-4 space-y-1">
                        <a href="{{ route('admin.chatbot.index') }}"
                            @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.chatbot.index') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                            Dashboard
                        </a>
                        <a href="{{ route('admin.chatbot.chat') }}"
                            @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.chatbot.chat') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                            </svg>
                            WhatsApp Chat
                        </a>
                        <a href="{{ route('admin.chatbot.inventaris') }}"
                            @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.chatbot.inventaris*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            Inventaris
                        </a>
                        <a href="{{ route('admin.chatbot.transaksi') }}"
                            @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.chatbot.transaksi*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            Transaksi
                        </a>
                    </div>
                </li>
            </ul>
        </div>

        {{-- Bagian bawah sidebar: Profil & Logout --}}
        <div class="mt-auto border-t border-gray-200 dark:border-gray-700 pt-4 px-6 space-y-2">
            {{-- Profil --}}
            <a href="{{ route('admin.profile.edit') }}"
                @click="sidebarOpen = false"
                class="flex items-center text-sm font-medium transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.profile.*') ? 'text-[#E62C37]' : '' }}">
                <div class="w-8 h-8 rounded-full bg-[#E62C37]/10 flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-[#E62C37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white text-sm leading-tight">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ Auth::user()->isAdmin() ? 'bg-[#E62C37]/20 text-[#E62C37]' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                        {{ Auth::user()->role }}
                    </span>
                </div>
            </a>

            {{-- Logout --}}
            <form method="POST" action="{{ route('admin.logout') }}" id="logoutForm">
                @csrf
                <button type="button" id="logoutBtn"
                    class="flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-[#E62C37] text-gray-500 dark:text-gray-400 py-2">
                    <svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="ml-3">Keluar (Logout)</span>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Modal Konfirmasi Logout --}}
<div id="logoutModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">
    <div class="bg-white dark:bg-[#1e2530] rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-[#E62C37]/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-[#E62C37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Konfirmasi Logout</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Apakah Anda yakin ingin keluar?</p>
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
            <button onclick="closeLogoutModal()"
                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                Batal
            </button>
            <button onclick="document.getElementById('logoutForm').submit()"
                class="px-4 py-2 text-sm text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-lg transition-colors">
                Ya, Keluar
            </button>
        </div>
    </div>
</div>

<script>
    document.getElementById('logoutBtn').addEventListener('click', function () {
        const modal = document.getElementById('logoutModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    function closeLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>