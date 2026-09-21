<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="turbo-prefetch" content="true">
    @php
        // SEO per halaman: title/description bisa dioverride lewat props komponen
        $seoTitle = $title ?? 'PT 4Putra Vertex Aviary — Penangkaran Burung Paruh Bengkok Premium';
        $seoDesc = $description ?? 'Penangkaran burung paruh bengkok premium di Surabaya Barat sejak 2019: African Grey, Macaw, Sun Conure, Monk Parakeet, Indian Ring Neck.';
        $seoUrl = request()->path() === '/'
            ? request()->getSchemeAndHttpHost() . '/'
            : request()->getSchemeAndHttpHost() . '/' . request()->path();
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <link rel="canonical" href="{{ $seoUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="PT 4Putra Vertex Aviary">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ asset('img/rfm-hero.png') }}">
    <link rel="icon" href="{{ asset('img/4Putraico.png') }}" type="image/png">
    @if (!empty($jsonLd))
    <script type="application/ld+json">@json($jsonLd)</script>
    @endif

    {{-- Preconnect ke external resources --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://res.cloudinary.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    {{-- Preload critical assets --}}
    <link rel="preload" href="{{ asset('img/rfm-hero.png') }}" as="image" fetchpriority="high" media="(min-width: 768px)">

    {{-- Dark mode init (harus di head untuk hindari glitch) --}}
    <script>
        const html = document.querySelector('html');
        const isLightOrAuto = localStorage.getItem('hs_theme') === 'light' || (localStorage.getItem('hs_theme') === 'auto' && !window.matchMedia('(prefers-color-scheme: dark)').matches);
        const isDarkOrAuto = localStorage.getItem('hs_theme') === 'dark' || (localStorage.getItem('hs_theme') === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        if (isLightOrAuto && html.classList.contains('dark')) html.classList.remove('dark');
        else if (isDarkOrAuto && html.classList.contains('light')) html.classList.remove('light');
        else if (isDarkOrAuto && !html.classList.contains('dark')) html.classList.add('dark');
        else if (isLightOrAuto && !html.classList.contains('light')) html.classList.add('light');
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Speculation Rules API: prefetch halaman publik saat hover/pointerdown (Chromium) --}}
    <script type="speculationrules">
        {"prefetch":[{"source":"document","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":"/lang/*"}}]},"eagerness":"conservative"}]}
    </script>

    {{-- Fallback micro-prefetcher untuk browser tanpa Speculation Rules (Firefox/Safari) --}}
    <script>
        (function () {
            var prefetched = new Set();
            document.addEventListener('pointerover', function (e) {
                var a = e.target.closest && e.target.closest('a[href^="/"]');
                if (!a || prefetched.has(a.href)) return;
                var url = a.getAttribute('href');
                if (url.indexOf('/admin') === 0 || url.indexOf('/lang/') === 0 || url.indexOf('/logout') === 0 || url.indexOf('/midtrans') === 0) return;
                prefetched.add(a.href);
                var l = document.createElement('link');
                l.rel = 'prefetch';
                l.href = url;
                document.head.appendChild(l);
            });
        })();
    </script>

    @stack('styles')
</head>

<body class="flex flex-col min-h-screen bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased transition-colors duration-300">

    <x-site.navbar></x-site.navbar>

    <main class="w-full pt-28 md:pt-36">
        {{ $slot }}
    </main>

    <x-site.footer></x-site.footer>

    <script src="{{ asset('js/media-protect.js') }}"></script>
    @stack('scripts')
</body>

</html>