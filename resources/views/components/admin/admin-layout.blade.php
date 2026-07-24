<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PT 4Putra Vertex Aviary</title>
    <!-- Memuat Aset Kompilasi NPM Lokal via Vite (Memastikan Dropzone, Anime, & Toast Terbaca) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#121824] text-gray-200 antialiased">
    <div class="flex h-screen w-full overflow-hidden">
        <!-- Memuat navigasi sidebar samping dasbor admin Anda -->
        @include('layouts.partials.sidebar')

        <div class="flex flex-col flex-1 w-full min-w-0">
            <main class="flex-1 overflow-y-auto bg-[#121824] p-6">
                <div class="max-w-7xl mx-auto w-full">
                    <!-- Slot dinamis penampung halaman views achievements -->
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>

</html>
