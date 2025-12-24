<nav id="navbar"
    class="fixed top-0 left-0 bg-transparent w-full flex items-center justify-between px-4 md:px-32 lg:px-48 xl:px-64 transition-all duration-500 z-50 py-8 md:py-12 text-white">

    <a href="/">
        <x-logo id="nav-logo" class="w-40 transition-colors duration-300"></x-logo>
    </a>

    <div class="hidden md:flex items-center gap-4 lg:gap-8 nav-items-container">
        <a href="/"
            class="nav-link font-medium transition-colors duration-300 border-b-2 {{ request()->is('/') ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900">
            {{ __('Home') }}
        </a>
        <a href="/collections"
            class="nav-link font-medium transition-colors duration-300 border-b-2 {{ request()->is('collections') ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900">
            {{ __('Collections') }}
        </a>
        <a href="/about"
            class="nav-link font-medium transition-colors duration-300 border-b-2 {{ request()->is('about') ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900">
            {{ __('About') }}
        </a>
        <a href="/contact"
            class="nav-link font-medium transition-colors duration-300 border-b-2 {{ request()->is('contact') ? 'border-[#E62C37]' : 'border-transparent hover:border-[#E62C37]' }} text-gray-900">
            {{ __('Contact') }}
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
        class="absolute top-full left-0 w-full bg-white border-t border-gray-100 shadow-xl z-40 transition-all duration-300 opacity-0 invisible -translate-y-2 origin-top">

        <ul class="flex flex-col p-4 font-medium space-y-2 text-gray-800">

            <li>
                <a href="/"
                    class="block py-3 px-4 transition-all duration-300 rounded-r-lg
               {{ request()->is('/')
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 hover:text-[#E62C37]' }}">
                    {{ __('Home') }}
                </a>
            </li>

            <li>
                <a href="/collections"
                    class="block py-3 px-4 transition-all duration-300 rounded-r-lg
               {{ request()->is('collections')
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 hover:text-[#E62C37]' }}">
                    {{ __('Collections') }}
                </a>
            </li>

            <li>
                <a href="/contact"
                    class="block py-3 px-4 transition-all duration-300 rounded-r-lg
               {{ request()->is('contact')
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 hover:text-[#E62C37]' }}">
                    {{ __('Contact') }}
                </a>
            </li>

            <li>
                <a href="/about"
                    class="block py-3 px-4 transition-all duration-300 rounded-r-lg
               {{ request()->is('about')
                   ? 'border-l-[5px] border-[#E62C37] bg-red-50 text-[#E62C37] font-bold'
                   : 'border-l-[5px] border-transparent hover:bg-gray-50 hover:text-[#E62C37]' }}">
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
            if (isScrolled) {
                // Mode Scrolled (Merah)
                navbar.classList.remove('bg-transparent', 'py-8', 'md:py-12');
                navbar.classList.add('bg-[#E62C37]', 'shadow-md', 'py-6', 'md:py-8');

                // Elemen jadi Putih
                navLinks.forEach(el => {
                    el.classList.remove('text-gray-900');
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
            } else {
                // Mode Top (Transparan)
                navbar.classList.add('bg-transparent', 'py-8', 'md:py-12');
                navbar.classList.remove('bg-[#E62C37]', 'shadow-md', 'py-6', 'md:py-8');

                // Elemen jadi Default
                navLinks.forEach(el => {
                    el.classList.add('text-gray-900');
                    el.classList.remove('text-white');
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
                logoPaths.forEach(p => p.setAttribute('fill', '#E62C37'));
            }
        };

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

            // ... sisa code resize watcher dll ...

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
