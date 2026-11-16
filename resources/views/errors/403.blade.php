<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="turbo-cache-control" content="no-cache">
    @vite('resources/css/app.css')
    <title>403 - Akses Ditolak</title>
</head>

<body class="flex flex-col min-h-screen bg-white">

    <div class="grow flex flex-col items-center justify-center text-sm max-md:px-4 w-full">
        <h1 class="text-8xl md:text-9xl font-black text-[#E62C37]">403</h1>
        <div class="h-1 w-16 rounded bg-[#E62C37] my-5 md:my-7"></div>
        <p class="text-2xl md:text-3xl font-bold text-gray-800">Akses Ditolak</p>
        <p class="text-sm md:text-base mt-4 text-gray-500 max-w-md text-center leading-relaxed">
            Anda tidak memiliki izin untuk mengakses halaman ini. Jika ingin masuk dengan akun lain, keluar terlebih dahulu.
        </p>
        <div class="flex items-center gap-4 mt-8 mb-10">
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit"
                    class="bg-black hover:bg-gray-800 px-7 py-3 text-white rounded-full font-medium active:scale-95 transition-all shadow-lg hover:shadow-xl">
                    Keluar & Ganti Akun
                </button>
            </form>
            <a href="/"
                class="border border-gray-300 px-7 py-3 text-gray-800 rounded-full font-medium hover:bg-gray-50 active:scale-95 transition-all">
                Kembali ke Beranda
            </a>
        </div>
    </div>

</body>

</html>
