<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="turbo-cache-control" content="no-cache">
    @vite('resources/css/app.css')
    <title>Sesi Kedaluwarsa</title>
</head>

<body class="flex flex-col min-h-screen bg-white">

    <div class="grow flex flex-col items-center justify-center text-sm max-md:px-4 w-full">
        <h1 class="text-8xl md:text-9xl font-black text-[#E62C37]">419</h1>
        <div class="h-1 w-16 rounded bg-[#E62C37] my-5 md:my-7"></div>
        <p class="text-2xl md:text-3xl font-bold text-gray-800">Sesi Kedaluwarsa</p>
        <p class="text-sm md:text-base mt-4 text-gray-500 max-w-md text-center leading-relaxed">
            Sesi Anda telah berakhir demi keamanan. Silakan masuk kembali untuk melanjutkan.
        </p>
        <div class="flex items-center gap-4 mt-8 mb-10">
            <a href="/login"
                class="bg-black hover:bg-gray-800 px-7 py-3 text-white rounded-full font-medium active:scale-95 transition-all shadow-lg hover:shadow-xl">
                Masuk Kembali
            </a>
        </div>
    </div>

</body>

</html>
