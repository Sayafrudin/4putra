<x-layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10">
        <div class="flex flex-col lg:flex-row items-start justify-between gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto">

            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left">
                <h1
                    class="text-2xl leading-tight md:text-3xl md:leading-tight lg:text-3xl lg:leading-tight font-bold uppercase">
                    {{ __('achievements.hero_welcome') }}
                    <span class="text-[#E62C37] font-normal">{{ __('achievements.hero_aviary') }}</span>
                </h1>

                <p class="text-gray-700 mt-6 text-justify md:text-md leading-relaxed max-w-2xl">
                    {{ __('achievements.hero_desc_1') }}
                    <br><br>
                    {{ __('achievements.hero_desc_2') }}
                    <br><br>
                    {{ __('achievements.hero_desc_3') }}
                </p>
            </div>

            <div class="w-full lg:w-5/12 flex flex-col space-y-4" x-data="{ activeImage: 'img/achievement1.jpg' }">

                <div
                    class="w-full aspect-[4/3] md:aspect-[5/4] bg-gray-100 rounded-xl overflow-hidden shadow-lg relative group shrink-0 grow-0 border border-gray-100">

                    <img :src="activeImage" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-50" x-transition:enter-end="opacity-100"
                        class="w-full h-full object-cover object-center transition-all duration-500"
                        alt="Main Featured Image">

                    <div class="absolute inset-0 ring-1 ring-black/5 rounded-xl pointer-events-none"></div>
                </div>

                <div class="w-full">
                    <div
                        class="flex gap-2 overflow-x-auto pb-1 px-1 scrollbar-hide snap-x cursor-pointer md:justify-center">

                        <img @click="activeImage = 'img/achievement1.jpg'" src="img/achievement1.jpg"
                            :class="activeImage === 'img/achievement1.jpg'
                                ?
                                'border-[#E62C37] opacity-100 ring-2 ring-[#E62C37]/30' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-16 h-12 md:w-20 md:h-16 rounded-md object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 1">

                        <img @click="activeImage = 'img/achievement2.jpeg'" src="img/achievement2.jpeg"
                            :class="activeImage === 'img/achievement2.jpeg'
                                ?
                                'border-[#E62C37] opacity-100 ring-2 ring-[#E62C37]/30' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-16 h-12 md:w-20 md:h-16 rounded-md object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 2">

                        <img @click="activeImage = 'img/achievement3.jpeg'" src="img/achievement3.jpeg"
                            :class="activeImage === 'img/achievement3.jpeg'
                                ?
                                'border-[#E62C37] opacity-100 ring-2 ring-[#E62C37]/30' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-16 h-12 md:w-20 md:h-16 rounded-md object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 3">

                        <img @click="activeImage = 'img/achievement4.jpeg'" src="img/achievement4.jpeg"
                            :class="activeImage === 'img/achievement4.jpeg'
                                ?
                                'border-[#E62C37] opacity-100 ring-2 ring-[#E62C37]/30' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-16 h-12 md:w-20 md:h-16 rounded-md object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 4">

                        <img @click="activeImage = 'img/achievement5.jpg'" src="img/achievement5.jpg"
                            :class="activeImage === 'img/achievement5.jpg'
                                ?
                                'border-[#E62C37] opacity-100 ring-2 ring-[#E62C37]/30' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-16 h-12 md:w-20 md:h-16 rounded-md object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 4">
                    </div>
                </div>
            </div>

        </div>
    </section>
</x-layout>
