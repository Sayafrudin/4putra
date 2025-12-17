<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip It - Vanilla Conversion</title>
    @vite('resources/css/app.css')

    <style>
        body {
            background-color: #2F2F2F;
        }
    </style>
</head>

<body>

    <x-navbar></x-navbar>

    <section>
        <main
            class="flex flex-col max-md:gap-20 md:flex-row pb-20 items-center justify-between mt-27 px-4 md:px-32 lg:px-48 xl:px-64 pt-10">
            <div class="flex flex-col items-center md:items-start">
                <h1
                    class="text-center md:text-left text-4xl leading-[46px] md:text-5xl md:leading-[68px] font-semibold max-w-xl text-white">
                    WELCOME TO OUR
                    <br>
                    <span class="text-[#E62C37]">AVIARY</span>
                </h1>
                <p class="text-center md:text-left text-sm text-white max-w-lg mt-2">
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
            <img src="https://raw.githubusercontent.com/prebuiltui/prebuiltui/main/assets/hero/hero-section-showcase-5.png"
                alt="hero" class="max-w-sm sm:max-w-md lg:max-w-lg 2xl:max-w-xl transition-all duration-300">
        </main>

    </section>


</body>

</html>
