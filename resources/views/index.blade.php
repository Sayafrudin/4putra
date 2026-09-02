<x-site.layout>

    <section class="relative w-full px-6 md:px-12 lg:px-16 pb-20 pt-10 md:pb-48">
        <div class="flex flex-col md:flex-row items-center justify-center gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto">

            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left relative z-10">
                <h1
                    class="text-4xl leading-tight md:text-5xl md:leading-tight lg:text-6xl lg:leading-tight font-bold uppercase">
                    {{ __('home.hero_welcome') }}
                    <span class="text-[#E62C37] font-normal">{{ __('home.hero_aviary') }}</span>
                </h1>

                <p class="text-gray-700 dark:text-gray-300 mt-6 md:text-md leading-relaxed max-w-2xl">
                    {{ __('home.hero_desc_1') }}
                    <br><br>
                    {{ __('home.hero_desc_2') }}
                    <br><br>
                    {{ __('home.hero_desc_3') }}
                </p>

                {{-- Credential chips: fakta dari copy hero, mengisi kolom kiri & meninggikan hero agar tubuh macaw bebas --}}
                <div class="mt-10 flex flex-wrap justify-center gap-3 md:justify-start">
                    <span class="rounded-full border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ __('home.chip_since') }}</span>
                    <span class="rounded-full border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ __('home.chip_location') }}</span>
                </div>
            </div>

            {{-- Mobile: disembunyikan (identitas visual tetap via desktop; PNG juga tak lagi diunduh mobile).
                 Desktop (md+): absolute besar memenuhi sisi kanan, ekor menjulur melewati batas section
                 (di belakang konten wrapper berikutnya). --}}
            <img src="{{ asset('img/rfm-hero.png') }}" alt="Red-fronted Macaw"
                class="hidden md:block md:absolute md:right-0 md:-top-24 lg:-top-36 md:z-0
                       md:w-[min(44vw,920px)] md:object-contain md:pointer-events-none">
        </div>
    </section>

    <script>
        window.CarouselData = @json($carouselCollections->isNotEmpty() ? $carouselCollections : null);
    </script>

    {{-- Wrapper full-width: konten render di depan ekor macaw yang menjulur dari hero (z-10 di atas z-0) --}}
    <div class="relative z-10">
        <section class="w-full max-w-7xl mx-auto py-10 px-6 flex flex-col gap-12">
        <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-20">

            <div class="w-full lg:w-1/3 flex flex-col items-center lg:items-start text-center lg:text-left space-y-4">
                <h1
                    class="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white leading-tight md:text-5xl md:leading-tight lg:leading-tight uppercase">
                    {{ __('home.explore_title') }} <br>
                    <span class="bg-clip-text text-[#E62C37] font-normal">
                        {{ __('home.popular_parrots') }}
                    </span>
                </h1>
                <p class="text-gray-700 dark:text-gray-300 text-md leading-relaxed ">
                    {{ __('home.popular_desc') }}
                </p>
            </div>

            <div id="marquee-wrapper"
                class="w-full lg:w-2/3 overflow-hidden relative cursor-pointer h-[24rem] flex items-center [mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)] [-webkit-mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)]">

                <div id="marquee-track" class="marquee-inner flex w-fit">
                    <div id="cards-container" class="flex items-center"></div>
                </div>
            </div>

        </div>

        <div class="flex justify-center w-full">
            <a href="/collections"
                class="group relative inline-flex items-center justify-center px-8 py-3 text-base font-semibold text-white bg-black rounded-full overflow-hidden transition-all duration-300 hover:w-60 hover:bg-gray-900 shadow-lg hover:shadow-xl hover:-translate-y-1">

                <span class="relative z-10 group-hover:-translate-x-2 transition-transform duration-300">
                    {{ __('home.btn_view_more') }}
                </span>

                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 absolute right-4 opacity-0 translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 z-10"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>

    </section>
    </div>
    <x-site.whatsapp />
</x-site.layout>
