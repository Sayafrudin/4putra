<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4Putra Vertex Aviary</title>
    <link rel="icon" href="img/4Putraico.png" type="image/png">

    {{-- Preconnect ke external resources --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Preload critical assets --}}
    <link rel="preload" href="{{ asset('img/buffont.png') }}" as="image" fetchpriority="high">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex flex-col min-h-screen bg-white antialiased">

    <x-site.navbar></x-site.navbar>

    <main class="w-full pt-28 md:pt-36">
        {{ $slot }}
    </main>

    <x-site.footer></x-site.footer>

    <script src="{{ asset('js/media-protect.js') }}"></script>
</body>

</html>