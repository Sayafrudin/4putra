<x-layout>

    <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10">
        <div class="flex flex-col md:flex-row items-center justify-center gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto">

            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left">
                <h1
                    class="text-4xl leading-tight md:text-5xl md:leading-tight lg:text-6xl lg:leading-tight font-bold uppercase">
                    {{ __('home.hero_welcome') }}
                    <span class="text-[#E62C37] font-normal">{{ __('home.hero_aviary') }}</span>
                </h1>

                <p class="text-gray-700 mt-6 text-justify md:text-md leading-relaxed max-w-2xl">
                    {{ __('home.hero_desc_1') }}
                    <br><br>
                    {{ __('home.hero_desc_2') }}
                    <br><br>
                    {{ __('home.hero_desc_3') }}
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
                <h1
                    class="text-4xl lg:text-5xl font-black text-gray-900 leading-tight md:text-5xl md:leading-tight lg:leading-tight uppercase">
                    {{ __('home.explore_title') }} <br>
                    <span class="bg-clip-text text-[#E62C37] font-normal">
                        {{ __('home.popular_parrots') }}
                    </span>
                </h1>
                <p class="text-gray-700 text-md leading-relaxed text-justify">
                    {{ __('home.popular_desc') }}
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
    <x-whatsapp />
</x-layout>
