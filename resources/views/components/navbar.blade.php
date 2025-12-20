<nav id="navbar"
    class="fixed top-0 left-0 bg-transparent w-full flex items-center justify-between px-4 md:px-32 lg:px-48 xl:px-64 transition-all duration-500 z-50 py-8 md:py-12 text-white">

    <a href="/">
        <x-logo class="w-40"></x-logo>
    </a>

    <div class="hidden md:flex items-center gap-4 lg:gap-8 nav-items-container">
        <x-nav-link href="/" :active="request()->is('/')">Home</x-nav-link>
        <x-nav-link href="/collections" :active="request()->is('collections')">Collections</x-nav-link>
        <x-nav-link href="/contact" :active="request()->is('contact')">Contact</x-nav-link>
        <x-nav-link href="/about" :active="request()->is('about')">About</x-nav-link>
    </div>

    <button id="hamburger-btn"
        class="group inline-flex w-12 h-12 md:hidden text-[#E62C37] text-center items-center justify-center"
        aria-pressed="false"
        onclick="this.setAttribute('aria-pressed', !(this.getAttribute('aria-pressed') === 'true'))">
        <svg class="w-6 h-6 fill-current pointer-events-none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
            <rect
                class="origin-center -translate-y-[5px] translate-x-[7px] transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.1)] group-[[aria-pressed=true]]:translate-x-0 group-[[aria-pressed=true]]:translate-y-0 group-[[aria-pressed=true]]:rotate-[315deg]"
                y="7" width="9" height="2" rx="1"></rect>
            <rect
                class="origin-center transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.8)] group-[[aria-pressed=true]]:rotate-45"
                y="7" width="16" height="2" rx="1"></rect>
            <rect
                class="origin-center translate-y-[5px] transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.1)] group-[[aria-pressed=true]]:translate-y-0 group-[[aria-pressed=true]]:rotate-[135deg]"
                y="7" width="9" height="2" rx="1"></rect>
        </svg>
    </button>

    <div id="mobile-menu"
        class="absolute top-full left-0 w-full bg-white border-t border-gray-100 shadow-xl z-40 
            transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] origin-top transform 
            opacity-0 invisible -translate-y-2 scale-95">

        <ul class="flex flex-col p-4 font-medium space-y-2">
            <li>
                <a href="/"
                    class="block py-2 px-3 {{ request()->is('/') ? ' border-l-13 border-[#E62C37] text-gray-900' : 'text-gray-900' }}"
                    aria-current="page">Home</a>
            </li>
            <li>
                <a href="/collections"
                    class="block py-2 px-3 {{ request()->is('collections') ? ' border-l-13 border-[#E62C37] text-gray-900' : 'text-gray-900' }}">Collections</a>
            </li>
            <li>
                <a href="/contact"
                    class="block py-2 px-3 {{ request()->is('contact') ? ' border-l-13 border-[#E62C37] text-gray-900' : 'text-gray-900' }}">Contact</a>
            </li>
            <li>
                <a href="/about"
                    class="block py-2 px-3 {{ request()->is('about') ? ' border-l-13 border-[#E62C37] text-gray-900' : 'text-gray-900' }}">About</a>
            </li>
        </ul>
    </div>

</nav>

<script>
    // 1. Selector DOM Elements
    // const scrollContainer = document.getElementById('scroll-container'); // HAPUS INI, KITA GANTI PAKE WINDOW
    const navLogo = document.getElementById('nav-logo');
    const logoPaths = navLogo.querySelectorAll('path');
    const navbar = document.getElementById('navbar');
    const navLinks = document.querySelectorAll('.nav-link');
    const lineIndicators = document.querySelectorAll('.line-indicator');
    const launchBtn = document.getElementById('launch-btn');
    const searchIcon = document.getElementById('search-icon');
    const loginBtn = document.getElementById('login-btn');
    const hamburgerBtn = document.getElementById('hamburger-btn');

    // Mobile Menu Elements
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const mobileLinks = document.querySelectorAll('.mobile-link');

    // 2. Fungsi Handle Scroll (Gunakan 'window' bukan 'scrollContainer')
    window.addEventListener('scroll', () => {
        // Gunakan window.scrollY untuk cek posisi scroll di browser
        const isScrolled = window.scrollY > 10;

        if (isScrolled) {
            // Apply Scrolled Styles
            navbar.classList.remove('bg-transparent', 'py-8', 'md:py-12', 'text-black');
            navbar.classList.add('bg-[#E62C37]', 'shadow-md', 'backdrop-blur-lg', 'py-6', 'md:py-8');
            hamburgerBtn.classList.add('text-white');
            logoPaths.forEach(p => p.setAttribute('fill', 'white'));
            navLinks.forEach(link => link.classList.add('text-white'));
            lineIndicators.forEach(line => {
                line.classList.remove('bg-[#E62C37]');
                line.classList.add('bg-white');
            });

        } else {
            // Revert to Default Styles
            navbar.classList.add('bg-transparent', 'py-8', 'md:py-12', 'text-black');
            navbar.classList.remove('bg-[#E62C37]', 'shadow-md', 'backdrop-blur-lg', 'py-6', 'md:py-8');
            logoPaths.forEach(p => p.setAttribute('fill', '#E62C37'));
            hamburgerBtn.classList.remove('text-white');
            hamburgerBtn.classList.add('text-[#E62C37]');
            navLinks.forEach(link => link.classList.remove('text-white'));
            lineIndicators.forEach(line => {
                line.classList.remove('bg-white');
                line.classList.add('bg-[#E62C37]');
            });
        }
    });

    // 3. Fungsi Mobile Menu (High Performance Animation)
    const toggleMenu = () => {
        const isClosed = mobileMenu.classList.contains('invisible');

        if (isClosed) {
            // --- ACTION: BUKA MENU ---
            // 1. Hapus state tertutup
            mobileMenu.classList.remove('invisible', 'opacity-0', '-translate-y-2', 'scale-95');
            // 2. Masukkan state terbuka (Normal)
            mobileMenu.classList.add('visible', 'opacity-100', 'translate-y-0', 'scale-100');
        } else {
            // --- ACTION: TUTUP MENU ---
            // 1. Hapus state terbuka
            mobileMenu.classList.remove('visible', 'opacity-100', 'translate-y-0', 'scale-100');
            // 2. Kembalikan ke state tertutup
            mobileMenu.classList.add('invisible', 'opacity-0', '-translate-y-2', 'scale-95');
        }
    };

    // Event Listeners
    hamburgerBtn.addEventListener('click', toggleMenu);

    // Auto close menu when clicking links
    const mobileLinkItems = mobileMenu.querySelectorAll('a');
    mobileLinkItems.forEach(link => {
        link.addEventListener('click', () => {
            // Tutup menu manual
            mobileMenu.classList.remove('visible', 'opacity-100', 'translate-y-0', 'scale-100');
            mobileMenu.classList.add('invisible', 'opacity-0', '-translate-y-2', 'scale-95');
        });
    });

    // Event Listeners
    hamburgerBtn.addEventListener('click', toggleMenu);
    closeMenuBtn.addEventListener('click', toggleMenu);

    // Auto close menu when clicking links
    mobileLinks.forEach(link => {
        link.addEventListener('click', toggleMenu);
    });
</script>
