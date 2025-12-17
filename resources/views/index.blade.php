<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip It - Vanilla Conversion</title>
    @vite('resources/css/app.css')

    <style>
        body {
            background-color: white;
        }
    </style>
</head>

<body>

    <x-navbar></x-navbar>

    <section class="w-full">
        <main
            class="flex flex-col md:flex-row items-center justify-center gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto px-6 md:px-12 lg:px-16 pt-40 pb-20">
            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left">
                <h1
                    class="text-4xl leading-tight md:text-5xl md:leading-tight lg:text-6xl lg:leading-tight font-bold text-gray-900">
                    WELCOME TO OUR <span class="text-[#E62C37]">AVIARY</span>
                </h1>

                <p class="text-gray-800 mt-6 text-base md:text-base leading-relaxed max-w-2xl">
                    Peternakan kami memulai perjalanannya pada tahun 2019 di Surabaya Barat dengan nama 4 Putra Parrot.
                    Pada awalnya kami mengawali langkah dengan fokus utama pada budidaya Lovebird. Namun seiring dengan
                    bertambahnya pengalaman serta pendalaman kami terhadap karakteristik paruh bengkok, kami
                    mempercayakan diri budidaya paruh bengkok dengan banyak spesies. Hal ini kami buktikan dengan
                    kesuksesan kami dalam menangkarkan spesies seperti Sun Conure, Monk Parakeet, African Grey, dll.
                    <br>
                    <br>
                    Konsistensi tersebut terus kami jaga hingga akhirnya kami mampu mengembangkan paruh bengkok besar
                    seperti Blue & Gold Macaw beserta varian Macaw lainnya.
                    <br>
                    <br>
                    Pada tahun 2025 Kami mengubah nama usaha penangkaran kami menjadi PT 4 Putra Vertex Aviary.
                    Perubahan nama menjadi PT. 4 Putra Vertex Aviary sebagai bentuk keseriusan kami dalam menangkar
                    paruh bengkok secara legal, dan mematuhi aturan yang ditetapkan oleh pemerintah Indonesia dalam
                    menangkar burung dilindungi maupun tidak dilindungi.
                </p>

            </div>

            <div class="flex-1 flex justify-center md:justify-end relative">
                <img src="https://img.sanishtech.com/u/6534cd5e25264afb7730a08c54fbd081.png" alt="hero"
                    class="max-w-xs md:max-w-sm lg:max-w-md xl:max-w-lg transition-all duration-300">
            </div>
        </main>

    </section>

    <section>

    </section>

</body>

</html>
