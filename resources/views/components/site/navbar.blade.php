<nav id="navbar"
    class="fixed top-0 left-0 bg-transparent w-full flex items-center justify-between px-4 md:px-32 lg:px-48 xl:px-64 transition-all duration-500 z-50 text-white">

    <a href="/">
        <x-logo id="nav-logo" class="w-40 transition-colors duration-300"></x-logo>
    </a>

    <div class="hidden md:flex items-center gap-4 lg:gap-8 nav-items-container">
        <a href="/"
            class="nav-link font-medium transition-colors duration-300 border-b-2 {{ request()->is('/') ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900 dark:text-white">
            {{ __('Home') }}
        </a>
        <a href="/collections"
            class="nav-link font-medium transition-colors duration-300 border-b-2 {{ request()->is('collections') ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900 dark:text-white">
            {{ __('Collections') }}
        </a>
        <a href="/facilities"
            class="nav-link font-medium transition-colors duration-300 border-b-2 {{ request()->is('facilities') ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900 dark:text-white">
            {{ __('Facilities') }}
        </a>

        {{-- Dropdown Activities --}}
        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
            <button type="button" @click="open = !open"
                class="nav-link flex items-center gap-1.5 font-medium transition-colors duration-300 border-b-2 {{ (request()->is('achievements') || request()->is('daily-activities')) ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900 dark:text-white">
                {{ __('Activities') }}
                <svg class="w-3 h-3 transition-transform duration-300" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-transition.origin.top
                class="absolute left-0 top-full mt-3 w-48 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-xl py-2 z-50">
                <a href="/daily-activities"
                    class="block px-4 py-2.5 text-sm transition-colors {{ request()->is('daily-activities') ? 'text-[#E62C37] font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                    {{ __('Daily Activities') }}
                </a>
                <a href="/achievements"
                    class="block px-4 py-2.5 text-sm transition-colors {{ request()->is('achievements') ? 'text-[#E62C37] font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                    {{ __('Achievements') }}
                </a>
            </div>
        </div>

        <a href="/about"
            class="nav-link font-medium transition-colors duration-300 border-b-2 {{ request()->is('about') ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900 dark:text-white">
            {{ __('About') }}
        </a>
    </div>

    <div class="hidden md:flex items-center ml-4">
        <span class="lang-label mr-3 text-sm font-bold text-[#E62C37] transition-colors duration-300">ID</span>

        <label class="relative flex items-center cursor-pointer">
            <input type="checkbox" id="lang-toggle" value="" class="sr-only peer"
                {{ app()->getLocale() == 'en' ? 'checked' : '' }}>

            <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer 
                        peer-checked:after:translate-x-full peer-checked:after:border-white 
                        after:content-[''] after:absolute after:top-[2px] after:left-[2px] 
                        after:bg-white after:border-gray-300 after:border after:rounded-full 
                        after:h-5 after:w-5 after:transition-all duration-500
                        peer-checked:bg-[#E62C37]">
            </div>
        </label>

        <span class="lang-label ml-3 text-sm font-bold text-[#E62C37] transition-colors duration-300">EN</span>

        {{-- Dark Mode Toggle --}}
        <div class="ml-4 flex items-center">
            <button type="button" class="hs-dark-mode-active:hidden block hs-dark-mode text-gray-600 dark:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 transition-colors" data-hs-theme-click-value="dark">
                <span class="group inline-flex shrink-0 justify-center items-center size-9">
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                </span>
            </button>
            <button type="button" class="hs-dark-mode-active:block hidden hs-dark-mode text-gray-600 dark:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 transition-colors" data-hs-theme-click-value="light">
                <span class="group inline-flex shrink-0 justify-center items-center size-9">
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                </span>
            </button>
        </div>
    </div>

    <button id="hamburger-btn"
        class="group inline-flex w-12 h-12 md:hidden text-[#E62C37] text-center items-center justify-center focus:outline-none transition-colors duration-300"
        aria-pressed="false">

        <svg class="w-6 h-6 fill-current pointer-events-none" viewBox="0 0 16 16">
            <rect
                class="origin-center -translate-y-[5px] translate-x-[7px] transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.1)] 
                     group-aria-pressed:translate-x-0 group-aria-pressed:translate-y-0 group-aria-pressed:rotate-[315deg]"
                y="7" width="9" height="2" rx="1"></rect>

            <rect
                class="origin-center transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.8)] 
                     group-aria-pressed:rotate-45"
                y="7" width="16" height="2" rx="1"></rect>

            <rect
                class="origin-center translate-y-[5px] transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.1)] 
                     group-aria-pressed:translate-y-0 group-aria-pressed:rotate-[135deg]"
                y="7" width="9" height="2" rx="1"></rect>
        </svg>
    </button>

    <div id="mobile-menu"
        class="absolute top-full left-0 w-full bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 shadow-xl z-40 transition-all duration-300 opacity-0 invisible -translate-y-2 origin-top">

        <ul class="flex flex-col p-4 font-medium space-y-2 text-gray-800 dark:text-gray-200">

            <li>
                <a href="/"
                    class="block py-3 px-4 transition-all duration-300
               {{ request()->is('/')
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 dark:bg-red-900/30 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                    {{ __('Home') }}
                </a>
            </li>

            <li>
                <a href="/collections"
                    class="block py-3 px-4 transition-all duration-300
               {{ request()->is('collections')
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 dark:bg-red-900/30 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                    {{ __('Collections') }}
                </a>
            </li>

            <li>
                <a href="/facilities"
                    class="block py-3 px-4 transition-all duration-300
               {{ request()->is('facilities')
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 dark:bg-red-900/30 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                    {{ __('Facilities') }}
                </a>
            </li>

            {{-- Accordion Activities --}}
            <li x-data="{ open: {{ (request()->is('achievements') || request()->is('daily-activities')) ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between py-3 px-4 transition-all duration-300
               {{ (request()->is('achievements') || request()->is('daily-activities'))
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 dark:bg-red-900/30 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                    {{ __('Activities') }}
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <ul x-show="open" x-transition.origin.top class="py-1 space-y-1">
                    <li>
                        <a href="/daily-activities"
                            class="block py-2.5 pl-10 pr-4 text-sm transition-all duration-300
                   {{ request()->is('daily-activities')
                       ? 'text-[#E62C37] font-bold'
                       : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                            {{ __('Daily Activities') }}
                        </a>
                    </li>
                    <li>
                        <a href="/achievements"
                            class="block py-2.5 pl-10 pr-4 text-sm transition-all duration-300
                   {{ request()->is('achievements')
                       ? 'text-[#E62C37] font-bold'
                       : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                            {{ __('Achievements') }}
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="/about"
                    class="block py-3 px-4 transition-all duration-300 
               {{ request()->is('about')
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 dark:bg-red-900/30 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-[#E62C37]' }}">
                    {{ __('About') }}
                </a>
            </li>

            <li class="border-t border-gray-500/50 pt-4 mt-2 flex gap-4 px-4 items-center justify-center">
                <a href="{{ route('lang.switch', 'id') }}"
                    class="px-4 py-2 transition-colors {{ app()->getLocale() == 'id' ? 'bg-[#E62C37] text-white font-bold shadow-md' : 'text-gray-500 hover:bg-gray-100' }}">
                    Indonesia
                </a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('lang.switch', 'en') }}"
                    class="px-4 py-2 transition-colors {{ app()->getLocale() == 'en' ? 'bg-[#E62C37] text-white font-bold shadow-md' : 'text-gray-500 hover:bg-gray-100' }}">
                    English
                </a>

                <span class="text-gray-300">|</span>

                {{-- Dark Mode Toggle Mobile --}}
                <button type="button" class="mobile-dark-toggle hs-dark-mode-active:hidden block hs-dark-mode text-gray-600 dark:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition-colors" data-hs-theme-click-value="dark">
                    <span class="group inline-flex shrink-0 justify-center items-center size-9">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    </span>
                </button>
                <button type="button" class="mobile-dark-toggle hs-dark-mode-active:block hidden hs-dark-mode text-gray-600 dark:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition-colors" data-hs-theme-click-value="light">
                    <span class="group inline-flex shrink-0 justify-center items-center size-9">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    </span>
                </button>
            </li>
        </ul>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- 1. SELECTORS ---
        const navbar = document.getElementById('navbar');
        const navLinks = document.querySelectorAll('.nav-link');
        const logoPaths = document.querySelectorAll('#nav-logo path');
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        // Toggle Selectors
        const langToggle = document.getElementById('lang-toggle');
        const langLabels = document.querySelectorAll('.lang-label');

        // --- DARK MODE TOGGLE ---
        const html = document.documentElement;
        const darkModeBtns = document.querySelectorAll('.hs-dark-mode');

        // Update tampilan tombol dark mode
        function updateDarkModeBtns() {
            const isDark = html.classList.contains('dark');
            darkModeBtns.forEach(btn => {
                const val = btn.getAttribute('data-hs-theme-click-value');
                if (val === 'dark') {
                    btn.style.display = isDark ? 'none' : 'block';
                } else {
                    btn.style.display = isDark ? 'block' : 'none';
                }
            });
        }

        // Set theme
        function setTheme(theme) {
            if (theme === 'dark') {
                html.classList.add('dark');
                html.classList.remove('light');
                localStorage.setItem('hs_theme', 'dark');
            } else {
                html.classList.remove('dark');
                html.classList.add('light');
                localStorage.setItem('hs_theme', 'light');
            }
            updateDarkModeBtns();
        }

        // Event listener untuk tombol dark mode
        darkModeBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const theme = this.getAttribute('data-hs-theme-click-value');
                setTheme(theme);
            });
        });

        // Update tombol saat load
        updateDarkModeBtns();

        // --- 2. TOGGLE EVENT LISTENER (Redirect) ---
        if (langToggle) {
            langToggle.addEventListener('change', function() {
                window.location.href = this.checked ? "{{ route('lang.switch', 'en') }}" :
                    "{{ route('lang.switch', 'id') }}";
            });
        }

        // --- 3. SCROLL LOGIC ---
        const handleScroll = () => {
            const isScrolled = window.scrollY > 10;
            const isDark = html.classList.contains('dark');

            if (isScrolled) {
                // Mode Scrolled (Merah) — sama untuk light & dark
                navbar.classList.remove('bg-transparent', 'py-8', 'md:py-12');
                navbar.classList.add('bg-[#E62C37]', 'shadow-md', 'py-6', 'md:py-8');

                navLinks.forEach(el => {
                    el.classList.remove('text-gray-900', 'dark:text-white');
                    el.classList.add('text-white');
                    if (el.classList.contains('border-[#E62C37]')) {
                        el.classList.remove('border-[#E62C37]');
                        el.classList.add('border-white');
                    }
                    el.classList.replace('hover:border-[#E62C37]', 'hover:border-white');
                });
                langLabels.forEach(el => {
                    el.classList.remove('text-[#E62C37]');
                    el.classList.add('text-white');
                });
                if (hamburgerBtn) {
                    hamburgerBtn.classList.remove('text-[#E62C37]');
                    hamburgerBtn.classList.add('text-white');
                }
                logoPaths.forEach(p => p.setAttribute('fill', 'white'));
                darkModeBtns.forEach(btn => {
                    btn.classList.remove('text-gray-600', 'dark:text-gray-300');
                    if (btn.classList.contains('mobile-dark-toggle')) {
                        // Mobile: gunakan warna yang kontras dengan bg-white drawer
                        btn.classList.add('text-gray-800', 'dark:text-gray-200');
                    } else {
                        // Desktop: text-white + hover translusen
                        btn.classList.add('text-white');
                        btn.classList.remove('hover:bg-gray-100', 'dark:hover:bg-gray-700');
                        btn.classList.add('hover:bg-white/20');
                    }
                });
            } else {
                // Mode Top (Transparan) — beda warna tergantung dark/light
                navbar.classList.add('bg-transparent', 'py-8', 'md:py-12');
                navbar.classList.remove('bg-[#E62C37]', 'shadow-md', 'py-6', 'md:py-8');

                navLinks.forEach(el => {
                    el.classList.remove('text-white');
                    if (isDark) {
                        el.classList.remove('text-gray-900');
                        el.classList.add('dark:text-white');
                    } else {
                        el.classList.add('text-gray-900');
                        el.classList.remove('dark:text-white');
                    }
                    if (el.classList.contains('border-white')) {
                        el.classList.remove('border-white');
                        el.classList.add('border-[#E62C37]');
                    }
                    el.classList.replace('hover:border-white', 'hover:border-[#E62C37]');
                });
                langLabels.forEach(el => {
                    el.classList.remove('text-white');
                    el.classList.add('text-[#E62C37]');
                });
                if (hamburgerBtn) {
                    hamburgerBtn.classList.remove('text-white');
                    hamburgerBtn.classList.add('text-[#E62C37]');
                }
                logoPaths.forEach(p => p.setAttribute('fill', isDark ? 'white' : '#E62C37'));
                darkModeBtns.forEach(btn => {
                    btn.classList.remove('text-white', 'text-gray-800', 'dark:text-gray-200', 'hover:bg-white/20');
                    if (btn.classList.contains('mobile-dark-toggle')) {
                        // Mobile: kembalikan ke default
                        btn.classList.add('text-gray-600', 'dark:text-gray-300');
                    } else {
                        // Desktop: kembalikan hover asli
                        btn.classList.add('hover:bg-gray-100', 'dark:hover:bg-gray-700');
                        if (isDark) {
                            btn.classList.remove('text-gray-600');
                            btn.classList.add('dark:text-gray-300');
                        } else {
                            btn.classList.add('text-gray-600');
                            btn.classList.remove('dark:text-gray-300');
                        }
                    }
                });
            }
        };

        // Re-apply scroll colors saat dark mode berubah
        const observer = new MutationObserver(() => { handleScroll(); });
        observer.observe(html, { attributes: true, attributeFilter: ['class'] });

        window.addEventListener('scroll', handleScroll);
        handleScroll();

        // --- 4. MOBILE MENU LOGIC (+ RESIZE FIX) ---
        // Fungsi helper buat tutup menu
        const closeMenu = () => {
            if (mobileMenu && !mobileMenu.classList.contains('invisible')) {
                mobileMenu.classList.add('invisible', 'opacity-0', '-translate-y-2');
                // Balikin icon hamburger kalau ada animasinya (opsional)
            }
        };

        if (hamburgerBtn && mobileMenu) {
            hamburgerBtn.addEventListener('click', () => {
                const isOpen = !mobileMenu.classList.contains('invisible');

                // --- TAMBAHAN LOGIC ANIMASI ICON ---
                // Kita toggle status aria-pressed (true/false)
                // CSS Tailwind akan otomatis membaca perubahan ini dan menjalankan animasi
                const isPressed = hamburgerBtn.getAttribute('aria-pressed') === 'true';
                hamburgerBtn.setAttribute('aria-pressed', !isPressed);

                // Logic Menu Buka/Tutup (Yang lama)
                if (isOpen) {
                    closeMenu();
                } else {
                    mobileMenu.classList.remove('invisible', 'opacity-0', '-translate-y-2');
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768) {
                    closeMenu();
                }
            });

            // PENTING: Update juga fungsi closeMenu biar iconnya balik normal kalau menu ketutup otomatis
            const closeMenu = () => {
                if (mobileMenu && !mobileMenu.classList.contains('invisible')) {
                    mobileMenu.classList.add('invisible', 'opacity-0', '-translate-y-2');

                    // RESET ICON JADI HAMBURGER BIASA
                    hamburgerBtn.setAttribute('aria-pressed', 'false');
                }
            };
        }
    });
</script>
