<x-layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10">
        <div class="flex flex-col md:flex-row items-center justify-center gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto">

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

            <div class="w-full lg:w-5/12 flex flex-col space-y-3" x-data="{
                activeImage: 'img/achievement1.jpg'
            }">

                <div class="w-full h-60 md:h-full overflow-hidden shadow-lg relative group grow-0 shrink-0">
                    <img :src="activeImage" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-50" x-transition:enter-end="opacity-100"
                        class="w-full h-90 object-cover object-center transition-all duration-500"
                        alt="Main Featured Image">
                    <div class="absolute inset-0 ring-1 ring-black/5 rounded-xl pointer-events-none"></div>
                </div>

                <div class="w-full">
                    <div class="flex gap-2 overflow-x-auto pb-1 px-1 scrollbar-hide snap-x cursor-pointer">
                        <img @click="activeImage = 'img/achievement1.jpg'" src="img/achievement1.jpg"
                            :class="activeImage === 'img/achievement1.jpg'
                                ?
                                'border-[#E62C37] opacity-100' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-14 h-16 md:w-32 md:h-24 object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 1">

                        <img @click="activeImage = 'img/achievement2.jpeg'" src="img/achievement2.jpeg"
                            :class="activeImage === 'img/achievement2.jpeg'
                                ?
                                'border-[#E62C37] opacity-100' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-14 h-16 md:w-32 md:h-24 object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 2">

                        <img @click="activeImage = 'img/achievement3.jpeg'" src="img/achievement3.jpeg"
                            :class="activeImage === 'img/achievement3.jpeg'
                                ?
                                'border-[#E62C37] opacity-100' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-14 h-16 md:w-32 md:h-24 object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 3">

                        <img @click="activeImage = 'img/achievement4.jpeg'" src="img/achievement4.jpeg"
                            :class="activeImage === 'img/achievement4.jpeg'
                                ?
                                'border-[#E62C37] opacity-100' :
                                'border-transparent opacity-60 hover:opacity-100'"
                            class="thumb shrink-0 w-14 h-16 md:w-32 md:h-24 object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                            alt="Thumb 4">
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('thumbnail-container');
        const mainImage = document.getElementById('main-image');
        const thumbs = document.querySelectorAll('.thumb');

        container.addEventListener('click', function(e) {
            // Pastikan yang diklik adalah elemen dengan class 'thumb'
            if (e.target.classList.contains('thumb')) {

                // 1. Ganti Gambar Utama
                // Tambah efek fade out dikit biar smooth (opsional logic)
                mainImage.style.opacity = '0.5';
                setTimeout(() => {
                    mainImage.src = e.target.src;
                    mainImage.style.opacity = '1';
                }, 150);

                // 2. Update Border Active State (Biar user tau mana yg dipilih)
                thumbs.forEach(img => {
                    img.classList.remove('border-[#E62C37]');
                    img.classList.add('border-transparent');
                });

                e.target.classList.remove('border-transparent');
                e.target.classList.add('border-[#E62C37]');
            }
        });
    });
</script>
