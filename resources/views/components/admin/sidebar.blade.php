@props(['activeRoute' => ''])

{{-- data-admin-sidebar + app.css: sidebar mobile tersembunyi SEBELUM Alpine siap
    (mencegah kilatan sidebar di setiap full page load); Alpine cukup toggle class --}}
<aside data-admin-sidebar :class="sidebarOpen ? 'sidebar-open' : ''"
    class="z-40 fixed inset-y-0 left-0 w-64 overflow-y-auto bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 transition-transform duration-300 ease-in-out lg:static lg:z-20 flex-shrink-0">
    <div class="flex flex-col h-full py-4 text-gray-500 dark:text-gray-400">
        <div>
            {{-- Close button mobile --}}
            <div class="flex items-center justify-between px-6 mb-4 lg:mb-0">
                <a class="text-xl font-extrabold tracking-wider text-gray-900 dark:text-white"
                    href="{{ route('admin.dashboard') }}" data-turbo="true">
                    4PUTRA<span class="text-[#E62C37]">.</span>
                </a>
                <button @click="sidebarOpen = false"
                    class="lg:hidden p-1 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>

            <ul class="mt-6" x-data="{ openMenu: {{ request()->routeIs('admin.achievements.*') || request()->routeIs('admin.about.*') || request()->routeIs('admin.collections.*') || request()->routeIs('admin.daily-activities.*') || request()->routeIs('admin.facilities.*') ? 'true' : 'false' }} }">
                <li class="relative px-6 py-3">
                    @if (request()->routeIs('admin.dashboard'))
                        <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg"
                            aria-hidden="true"></span>
                    @endif
                    <a class="inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.dashboard') ? 'text-gray-900 dark:text-white font-bold' : '' }}"
                        href="{{ route('admin.dashboard') }}" data-turbo="true" @click="sidebarOpen = false">
                        <x-tabler-layout-dashboard class="w-5 h-5" />
                        <span class="ml-4">Beranda (Dashboard)</span>
                    </a>
                </li>

                {{-- Manajemen Konten hanya admin (route digate server-side admin.only);
                    tiap anak difilter hak akses menu (NULL = penuh) --}}
                @if (Auth::user()->isAdmin() && (\App\Support\AdminMenus::boleh(Auth::user(), 'collections') || \App\Support\AdminMenus::boleh(Auth::user(), 'facilities') || \App\Support\AdminMenus::boleh(Auth::user(), 'daily-activities') || \App\Support\AdminMenus::boleh(Auth::user(), 'achievements')))
                <li class="relative px-6 py-3">
                    @if (request()->routeIs('admin.achievements.*') ||
                            request()->routeIs('admin.about.*') ||
                            request()->routeIs('admin.collections.*') ||
                            request()->routeIs('admin.daily-activities.*') ||
                            request()->routeIs('admin.facilities.*'))
                        <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg"
                            aria-hidden="true"></span>
                    @endif
                    <button @click="openMenu = !openMenu"
                        class="inline-flex items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.achievements.*') || request()->routeIs('admin.about.*') || request()->routeIs('admin.collections.*') || request()->routeIs('admin.daily-activities.*') || request()->routeIs('admin.facilities.*') ? 'text-gray-900 dark:text-white font-bold' : '' }}">
                        <span class="inline-flex items-center">
                            <x-tabler-package class="w-5 h-5" />
                            <span class="ml-4">Manajemen Konten</span>
                        </span>
                        <x-tabler-chevron-down class="w-4 h-4 transition-transform duration-200" />
                    </button>

                    <div x-show="openMenu" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1" class="mt-2 ml-4 space-y-1">
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'collections'))
                        <a href="{{ route('admin.collections.index') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.collections.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-category class="w-4 h-4 mr-2" />
                            Koleksi (Collections)
                        </a>
                        @endif
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'facilities'))
                        {{-- Full-reload: script halaman = ES module (tak dieksekusi ulang Turbo, module map cache) --}}
                        <a href="{{ route('admin.facilities.index') }}" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.facilities.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-building-store class="w-4 h-4 mr-2" />
                            Fasilitas (Facilities)
                        </a>
                        @endif
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'daily-activities'))
                        {{-- Full-reload: script halaman = ES module (tak dieksekusi ulang Turbo, module map cache) --}}
                        <a href="{{ route('admin.daily-activities.index') }}" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.daily-activities.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-calendar class="w-4 h-4 mr-2" />
                            Aktivitas Harian (Daily Activities)
                        </a>
                        @endif
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'achievements'))
                        {{-- Full-reload: script halaman = ES module (achievements+about) tak dieksekusi ulang Turbo --}}
                        <a href="{{ route('admin.achievements.index') }}" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.achievements.*') || request()->routeIs('admin.about.*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-clipboard-list class="w-4 h-4 mr-2" />
                            Pencapaian (Achievements)
                        </a>
                        @endif
                    </div>
                </li>
                @endif

                {{-- Menu Manajemen Farm (data burung, bukan urusan chatbot); tiap anak difilter hak akses menu --}}
                @if (\App\Support\AdminMenus::boleh(Auth::user(), 'aviary') || \App\Support\AdminMenus::boleh(Auth::user(), 'kandang') || \App\Support\AdminMenus::boleh(Auth::user(), 'vaksinasi') || \App\Support\AdminMenus::boleh(Auth::user(), 'telur') || \App\Support\AdminMenus::boleh(Auth::user(), 'baby') || \App\Support\AdminMenus::boleh(Auth::user(), 'stok'))
                <li class="relative px-6 py-3"
                    x-data="{ openFarm: {{ request()->routeIs('admin.aviary*') || request()->routeIs('admin.kandang*') || request()->routeIs('admin.vaksinasi*') || request()->routeIs('admin.telur*') || request()->routeIs('admin.baby*') || request()->routeIs('admin.stok*') ? 'true' : 'false' }} }">
                    @if (request()->routeIs('admin.aviary*') || request()->routeIs('admin.kandang*') || request()->routeIs('admin.vaksinasi*') || request()->routeIs('admin.telur*') || request()->routeIs('admin.baby*') || request()->routeIs('admin.stok*'))
                        <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg"
                            aria-hidden="true"></span>
                    @endif
                    <button @click="openFarm = !openFarm"
                        class="inline-flex items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.aviary*') || request()->routeIs('admin.kandang*') || request()->routeIs('admin.vaksinasi*') || request()->routeIs('admin.telur*') || request()->routeIs('admin.baby*') || request()->routeIs('admin.stok*') ? 'text-gray-900 dark:text-white font-bold' : '' }}">
                        <span class="inline-flex items-center">
                            <x-tabler-building-warehouse class="w-5 h-5" />
                            <span class="ml-4">Manajemen Farm</span>
                        </span>
                        <x-tabler-chevron-down class="w-4 h-4 transition-transform duration-200" />
                    </button>

                    <div x-show="openFarm" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1" class="mt-2 ml-4 space-y-1">
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'aviary'))
                        <a href="{{ route('admin.aviary.index') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.aviary*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-fence class="w-4 h-4 mr-2" />
                            Aviary
                        </a>
                        @endif
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'kandang'))
                        <a href="{{ route('admin.kandang.index') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.kandang*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-home-2 class="w-4 h-4 mr-2" />
                            Kandang
                        </a>
                        @endif
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'vaksinasi'))
                        <a href="{{ route('admin.vaksinasi.index') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.vaksinasi*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-vaccine class="w-4 h-4 mr-2" />
                            Obat & Vaksinasi
                        </a>
                        @endif
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'telur'))
                        <a href="{{ route('admin.telur.index') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.telur*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-egg class="w-4 h-4 mr-2" />
                            Telur & Incubator
                        </a>
                        @endif
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'baby'))
                        <a href="{{ route('admin.baby.index') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.baby*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-eggs class="w-4 h-4 mr-2" />
                            Baby
                        </a>
                        @endif
                        @if (\App\Support\AdminMenus::boleh(Auth::user(), 'stok'))
                        <a href="{{ route('admin.stok.index') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.stok*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-packages class="w-4 h-4 mr-2" />
                            Stok Makanan
                        </a>
                        @endif
                    </div>
                </li>
                @endif

                {{-- Menu Keuangan (uang krusial farm) — difilter hak akses menu --}}
                @if (\App\Support\AdminMenus::boleh(Auth::user(), 'keuangan'))
                <li class="relative px-6 py-3">
                    @if (request()->routeIs('admin.keuangan*'))
                        <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg"
                            aria-hidden="true"></span>
                    @endif
                    <a href="{{ route('admin.keuangan.index') }}" data-turbo="true" @click="sidebarOpen = false"
                        class="inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.keuangan*') ? 'text-gray-900 dark:text-white font-bold' : '' }}">
                        <x-tabler-wallet class="w-5 h-5" />
                        <span class="ml-4">Keuangan</span>
                    </a>
                </li>
                @endif

                {{-- Menu Manajemen User (hanya admin PENUH — menu_access NULL) --}}
                @if (Auth::user()->isAdmin() && Auth::user()->menu_access === null)
                    <li class="relative px-6 py-3">
                        @if (request()->routeIs('admin.users.*'))
                            <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg"
                                aria-hidden="true"></span>
                        @endif
                        <a class="inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.users.*') ? 'text-gray-900 dark:text-white font-bold' : '' }}"
                            {{-- Full-reload: presence users pakai inline module (RTDB) tak dieksekusi ulang Turbo --}}
                            href="{{ route('admin.users.index') }}" @click="sidebarOpen = false">
                            <x-tabler-users class="w-5 h-5" />
                            <span class="ml-4">Kelola Akun User</span>
                        </a>
                    </li>
                @endif

                {{-- Menu Chatbot WhatsApp: fitur skripsi → hanya admin, local-only, tidak dirender di produksi --}}
                @if (Auth::user()->isAdmin() && ! app()->environment('production'))
                <li class="relative px-6 py-3" x-data="{ openChatbot: {{ request()->routeIs('admin.chatbot.*') ? 'true' : 'false' }} }">
                    @if (request()->routeIs('admin.chatbot.*'))
                        <span class="absolute inset-y-0 left-0 w-1 bg-[#E62C37] rounded-tr-lg rounded-br-lg"
                            aria-hidden="true"></span>
                    @endif
                    <button @click="openChatbot = !openChatbot"
                        class="inline-flex items-center justify-between w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.chatbot.*') ? 'text-gray-900 dark:text-white font-bold' : '' }}">
                        <span class="inline-flex items-center">
                            <x-tabler-message-chatbot class="w-5 h-5" />
                            <span class="ml-4">Chatbot WhatsApp</span>
                        </span>
                        <x-tabler-chevron-down class="w-4 h-4 transition-transform duration-200" />
                    </button>

                    <div x-show="openChatbot" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1" class="mt-2 ml-4 space-y-1">
                        <a href="{{ route('admin.chatbot.index') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.chatbot.index') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-layout-grid class="w-4 h-4 mr-2" />
                            Ringkasan
                        </a>
                        <a href="{{ route('admin.chatbot.inventaris') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.chatbot.inventaris*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-archive class="w-4 h-4 mr-2" />
                            Inventaris Burung
                        </a>
                        <a href="{{ route('admin.chatbot.chat') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.chatbot.chat') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-message class="w-4 h-4 mr-2" />
                            WhatsApp Chat
                        </a>
                        <a href="{{ route('admin.chatbot.transaksi') }}" data-turbo="true" @click="sidebarOpen = false"
                            class="flex items-center px-3 py-2 text-sm rounded transition-colors duration-150 {{ request()->routeIs('admin.chatbot.transaksi*') ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : 'hover:text-gray-800 dark:hover:text-gray-200' }}">
                            <x-tabler-coins class="w-4 h-4 mr-2" />
                            Transaksi
                        </a>
                    </div>
                </li>
                @endif
            </ul>
        </div>

        {{-- Bagian bawah sidebar: Profil & Logout --}}
        <div class="mt-auto border-t border-gray-200 dark:border-gray-700 pt-4 px-6 space-y-2">
            {{-- Profil --}}
            <a href="{{ route('admin.profile.edit') }}" data-turbo="true" @click="sidebarOpen = false"
                class="flex items-center text-sm font-medium transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 {{ request()->routeIs('admin.profile.*') ? 'text-[#E62C37]' : '' }}">
                <div class="w-8 h-8 rounded-full bg-[#E62C37]/10 flex items-center justify-center mr-3">
                    <x-tabler-user class="w-4 h-4 text-[#E62C37]" />
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white text-sm leading-tight">
                        {{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->email ?? '—' }}</p>
                    <span
                        class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ Auth::user()->isAdmin() ? 'bg-[#E62C37]/20 text-[#E62C37]' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                        {{ Auth::user()->role }}
                    </span>
                </div>
            </a>

            {{-- Logout --}}
            <form method="POST" action="{{ route('admin.logout') }}" id="logoutForm">
                @csrf
                <button type="button" id="logoutBtn"
                    class="flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-[#E62C37] text-gray-500 dark:text-gray-400 py-2">
                    <x-tabler-logout class="w-5 h-5" />
                    <span class="ml-3">Keluar (Logout)</span>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Modal Konfirmasi Logout --}}
<div id="logoutModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">
    <div
        class="bg-white dark:bg-[#1e2530] rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-[#E62C37]/10 flex items-center justify-center">
                <x-tabler-logout class="w-5 h-5 text-[#E62C37]" />
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
    document.getElementById('logoutBtn').addEventListener('click', function() {
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
