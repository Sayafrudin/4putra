<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4Putra Vertex Aviary</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex flex-col min-h-screen bg-white">

    <x-navbar></x-navbar>

    <main class="w-full pt-28 md:pt-36">
        <!-- Konten utama di sini -->
        {{ $slot }}
    </main>

    <x-footer></x-footer>

</body>

</html>
