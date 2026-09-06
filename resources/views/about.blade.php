<x-site.layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10">
        <div class="flex flex-col md:flex-row items-center justify-center gap-10 lg:gap-12 max-w-7xl mx-auto">
            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left">
                <h1
                    class="text-3xl leading-tight md:text-5xl md:leading-tight lg:text-6xl lg:leading-tight font-bold uppercase">
                    {{ __('about.hero_welcome') }}
                    <span class="text-[#E62C37] font-normal">{{ __('about.hero_aviary') }}</span>
                </h1>

                <p class="text-gray-700 dark:text-gray-300 mt-6 md:text-md leading-relaxed">
                    {{ __('about.hero_desc_1') }}
                    <br><br>
                    {{ __('about.hero_desc_2') }}
                    <br><br>
                    {{ __('about.hero_desc_3') }}
                </p>
            </div>

            <div class="flex-1 lg:flex-none lg:w-4/12 flex justify-center md:justify-end relative">
                <div class="w-full max-w-sm rounded-2xl overflow-hidden shadow-xl aspect-[4/5] relative group">
                    <img src="{{ asset('img/achievement1.jpg') }}" alt="About Hero"
                        class="w-full h-full object-cover transition-all duration-500 hover:scale-105">
                    <div class="absolute inset-0 ring-1 ring-black/5 pointer-events-none"></div>
                </div>

                {{-- VIDEO GDRIVE (rasio mengikuti video): aktifkan salah satu varian di bawah,
                     ganti GANTI_FILE_ID dengan ID dari link GDrive
                     (contoh: https://drive.google.com/file/d/GANTI_FILE_ID/view),
                     lalu hapus blok <img> di atas dan hapus pembuka/penutup komentar ini.
                     Note: player GDrive otomatis letterbox, video selalu tampil pada rasio aslinya.

                     VARIAN LANDSCAPE 16:9 (default):
                <div class="w-full max-w-md rounded-2xl overflow-hidden shadow-xl aspect-video relative group">
                    <iframe src="https://drive.google.com/file/d/GANTI_FILE_ID/preview"
                        class="w-full h-full" allow="autoplay" allowfullscreen loading="lazy"></iframe>
                    <div class="absolute inset-0 ring-1 ring-black/5 pointer-events-none"></div>
                </div>

                     VARIAN VERTICAL 9:16 (video tegak, tinggal pindahkan blok ini ke aktif):
                <div class="w-full max-w-xs mx-auto rounded-2xl overflow-hidden shadow-xl aspect-[9/16] relative group">
                    <iframe src="https://drive.google.com/file/d/GANTI_FILE_ID/preview"
                        class="w-full h-full" allow="autoplay" allowfullscreen loading="lazy"></iframe>
                    <div class="absolute inset-0 ring-1 ring-black/5 pointer-events-none"></div>
                </div>
                --}}
            </div>
        </div>
    </section>

    <section class="w-full px-6 md:px-12 lg:px-16 pb-10 pt-10 bg-[#F3F4F6] dark:bg-gray-800">
        <h1 class="text-4xl font-bold text-slate-800 dark:text-slate-100 text-center">
            {{ __('about.team_title') }}
            <span class="font-medium text-[#E62C37]">
                {{ __('about.team_leadership') }}
            </span>
        </h1>
        <p class="text-slate-600 dark:text-slate-400 text-center mt-2">
            {{ __('about.team_desc') }}
        </p>

        <x-site.divider>{{ __('about.team_devide') }}</x-site.divider>

        <div class="flex flex-wrap items-center justify-center gap-10">

            <x-site.card :zoomable="false" gambar="{{ asset('img/manager.png') }}" name="Rachmad Hidayat"
                SName="{{ __('about.team_role3') }}">
            </x-site.card>

            <x-site.card :zoomable="false" gambar="{{ asset('img/direktur.png') }}" name="Dedy Murya Budi, SE"
                SName="{{ __('about.team_role1') }}">
            </x-site.card>

            <x-site.card :zoomable="false" gambar="{{ asset('img/komisaris.png') }}" name="Syafrudin Hendra Lumanto"
                SName="{{ __('about.team_role2') }}">
            </x-site.card>


        </div>

        {{-- <x-site.divider>Operational</x-site.divider>

        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-site.card name="..." SName="...">
            </x-site.card>

            <x-site.card name="..." SName="...">
            </x-site.card>

            <x-site.card name="..." SName="...">
            </x-site.card>
        </div> --}}
    </section>
    <section id="contact" class="scroll-mt-28 md:scroll-mt-36 bg-white dark:bg-gray-900">
        <div class="container px-6 py-10 mx-auto">

            <div class="text-center mb-12">
                <h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white md:text-4xl">{{ __('contact.hero_welcome') }} <span
                        class="text-[#E62C37]">{{ __('contact.hero_welcome2') }}</span></h1>
                <p class="mt-3 text-gray-500 dark:text-gray-400 max-w-xl mx-auto">
                    {{ __('contact.hero_desc_1') }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-1">

                    <div class="flex flex-col items-start p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl hover:shadow-md transition-shadow">
                        <span class="inline-block p-3 text-[#E62C37] rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <h2 class="mt-4 text-lg font-bold text-gray-800 dark:text-gray-200">Email</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('contact.card1_desc_1') }}</p>
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=info@4putravertex.com" target="_blank"
                            rel="noopener noreferrer" class="mt-2 text-sm font-medium text-[#E62C37] hover:underline">
                            info@4putravertex.com
                        </a>
                    </div>

                    <div class="flex flex-col items-start p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl hover:shadow-md transition-shadow">
                        <span class="inline-block p-3 text-[#E62C37] rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                        </span>
                        <h2 class="mt-4 text-lg font-bold text-gray-800 dark:text-gray-200">{{ __('contact.card2_desc_1') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('contact.card2_desc_2') }}</p>
                        <p class="mt-2 text-sm font-medium text-[#E62C37]">
                            Jl. Manukan Lor VIII D No.1, Banjar Sugihan, Surabaya, Jawa Timur
                        </p>
                    </div>

                    <div class="flex flex-col items-start p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl hover:shadow-md transition-shadow">
                        <span class="inline-block p-3 text-[#E62C37] rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                            </svg>
                        </span>
                        <h2 class="mt-4 text-lg font-bold text-gray-800 dark:text-gray-200">{{ __('contact.card3_desc_1') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('contact.card4_desc_2') }}</p>
                        <a href="https://wa.me/6282132267308" target="_blank"
                            class="mt-2 text-sm font-medium text-[#E62C37] hover:underline">
                            +62 821-3226-7308 (Dedy)
                        </a>
                        <a href="https://wa.me/6285607352356" target="_blank"
                            class="mt-1 text-sm font-medium text-[#E62C37] hover:underline">
                            +62 856-0735-2356 (Syafrudin)
                        </a>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-2xl lg:col-span-2 h-96 lg:h-auto shadow-lg relative border border-gray-200 dark:border-gray-700">
                    <iframe width="100%" height="100%" style="border:0;" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade" class="absolute inset-0 w-full h-full"
                        src="https://maps.google.com/maps?q=4+PUTRA+PARROT+Surabaya&t=&z=15&ie=UTF8&iwloc=&output=embed">
                    </iframe>
                </div>
            </div>
        </div>
    </section>
    <x-site.whatsapp />
</x-site.layout>
