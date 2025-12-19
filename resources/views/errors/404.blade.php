<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @vite('resources/css/app.css')
    <title>404 - Page Not Found</title>
</head>

<body class="flex flex-col min-h-screen bg-white">

    <div class="grow flex flex-col items-center justify-center text-sm max-md:px-4 pt-24 md:pt-32 w-full">
        <h1 class="text-8xl md:text-9xl font-black text-[#E62C37]">404</h1>
        <div class="h-1 w-16 rounded bg-[#E62C37] my-5 md:my-7"></div>
        <p class="text-2xl md:text-3xl font-bold text-gray-800">Page Not Found</p>
        <p class="text-sm md:text-base mt-4 text-gray-500 max-w-md text-center leading-relaxed">
            The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
        <div class="flex items-center gap-4 mt-8 mb-10">
            <a href="/"
                class="bg-black hover:bg-gray-800 px-7 py-3 text-white rounded-full font-medium active:scale-95 transition-all shadow-lg hover:shadow-xl">
                Return Home
            </a>
            <a href="/contact"
                class="border border-gray-300 px-7 py-3 text-gray-800 rounded-full font-medium hover:bg-gray-50 active:scale-95 transition-all">
                Contact Support
            </a>
        </div>
    </div>

</body>

</html>
