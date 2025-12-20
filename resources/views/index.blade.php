<x-layout>

    <section class="w-full px-6 md:px-12 lg:px-16 pb-20">
        <div class="flex flex-col md:flex-row items-center justify-center gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto">
            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left">
                <h1 class="text-4xl leading-tight md:text-5xl md:leading-tight lg:text-6xl lg:leading-tight font-bold">
                    WELCOME TO OUR <span class="text-[#E62C37] font-normal">AVIARY</span>
                </h1>
                <p class="text-gray-700 mt-6 text-justify md:text-md leading-relaxed max-w-2xl">
                    Peternakan kami memulai perjalanannya pada tahun 2019 di Surabaya Barat dengan nama 4 Putra
                    Parrot.
                    Pada awalnya kami mengawali langkah dengan fokus utama pada budidaya Lovebird. Namun seiring
                    dengan
                    bertambahnya pengalaman serta pendalaman kami terhadap karakteristik paruh bengkok, kami
                    mempercayakan diri budidaya paruh bengkok dengan banyak spesies.
                    <br><br>
                    Konsistensi tersebut terus kami jaga hingga akhirnya kami mampu mengembangkan paruh bengkok
                    besar
                    seperti Blue & Gold Macaw beserta varian Macaw lainnya.
                    <br><br>
                    Pada tahun 2025 Kami mengubah nama usaha penangkaran kami menjadi PT 4 Putra Vertex Aviary.
                    Perubahan nama menjadi PT. 4 Putra Vertex Aviary sebagai bentuk keseriusan kami dalam menangkar
                    paruh bengkok secara legal.
                </p>
            </div>

            <div class="flex-1 flex justify-center md:justify-end relative">
                <img src="{{ asset('img/buffont.png') }}" alt="hero"
                    class="max-w-xs md:max-w-sm lg:max-w-md xl:max-w-lg transition-all duration-300 drop-shadow-xl">
            </div>
        </div>
    </section>

    <section class="w-full max-w-7xl mx-auto py-10 px-6 flex flex-col gap-12">
        <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-20">
            <div class="w-full lg:w-1/3 flex flex-col items-center lg:items-start text-center lg:text-left space-y-4">
                {{-- <span
                        class="px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-600 text-sm font-bold tracking-wide uppercase">
                        Latest Blog
                    </span> --}}
                <h1
                    class="text-4xl lg:text-5xl font-black text-gray-900 leading-tight md:text-5xl md:leading-tight lg:leading-tight">
                    EXPLORE OUR <br>
                    <span class="bg-clip-text text-[#E62C37] font-normal">
                        Popular Parrots
                    </span>
                </h1>
                <p class="text-gray-500 text-md leading-relaxed text-justify">
                    Temukan koleksi burung paruh bengkok favorit kami, mulai dari Sun Conure yang lincah hingga
                    Macaw yang gagah, semuanya hasil tangkaran sendiri yang jinak dan siap menjadi teman barumu.
                </p>
            </div>

            <div id="marquee-wrapper"
                class="w-full lg:w-2/3 overflow-hidden relative cursor-pointer h-[24rem] flex items-center">
                <div
                    class="absolute left-0 top-0 h-full w-24 z-20 pointer-events-none bg-linear-to-r from-white to-transparent">
                </div>
                <div
                    class="absolute right-0 top-0 h-full w-24 z-20 pointer-events-none bg-linear-to-l from-white to-transparent">
                </div>

                <div id="marquee-track" class="marquee-inner flex w-fit">
                    <div id="cards-container" class="flex items-center"></div>
                </div>
            </div>

        </div>

        <div class="flex justify-center w-full">
            <a href="/collections"
                class="group relative inline-flex items-center justify-center px-8 py-3 text-base font-semibold text-white bg-black rounded-full overflow-hidden transition-all duration-300 hover:w-60 hover:bg-gray-900 shadow-lg hover:shadow-xl hover:-translate-y-1">
                <span class="relative z-10 group-hover:-translate-x-2 transition-transform duration-300">View More
                    Collections</span>
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 absolute right-4 opacity-0 translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 z-10"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>

    </section>

</x-layout>
