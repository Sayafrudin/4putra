<x-layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10">
        <div class="flex flex-col md:flex-row items-center justify-center gap-10 lg:gap-12 max-w-7xl mx-auto">
            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left">
                <h1
                    class="text-3xl leading-tight md:text-5xl md:leading-tight lg:text-6xl lg:leading-tight font-bold uppercase">
                    {{ __('about.hero_welcome') }}
                    <span class="text-[#E62C37] font-normal">{{ __('about.hero_aviary') }}</span>
                </h1>

                <p class="text-gray-700 mt-6 text-justify md:text-md leading-relaxed">
                    {{ __('about.hero_desc_1') }}
                    <br><br>
                    {{ __('about.hero_desc_2') }}
                    <br><br>
                    {{ __('about.hero_desc_3') }}
                </p>
            </div>

            <div class="flex-1 lg:flex-none lg:w-4/12 flex justify-center md:justify-end relative">
                <div class="w-full max-w-sm overflow-hidden shadow-xl aspect-[4/5] relative group">
                    <img src="{{ asset('img/achievement1.jpg') }}" alt="About Hero"
                        class="w-full h-full object-cover transition-all duration-500 hover:scale-105">
                    <div class="absolute inset-0 ring-1 ring-black/5 pointer-events-none"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full px-6 md:px-12 lg:px-16 pb-10 pt-10 bg-[#F3F4F6]">
        <h1 class="text-4xl font-bold text-slate-800 text-center">
            {{ __('about.team_title') }}
            <span class="font-medium text-[#E62C37]">
                {{ __('about.team_leadership') }}
            </span>
        </h1>
        <p class="text-slate-600 text-center mt-2">
            {{ __('about.team_desc') }}
        </p>

        <x-divider>{{ __('about.team_devide') }}</x-divider>

        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-card gambar="{{ asset('img/direktur.png') }}" name="Dedy Murya Budi, SE"
                SName="{{ __('about.team_role1') }}">
            </x-card>

            <x-card gambar="{{ asset('img/komisaris.png') }}" name="Syafrudin Hendra Lumanto"
                SName="{{ __('about.team_role2') }}">
            </x-card>

            <x-card gambar="{{ asset('img/manager.png') }}" name="Rachmad Hidayat"
                SName="{{ __('about.team_role3') }}">
            </x-card>
        </div>

        {{-- <x-divider>Operational</x-divider>

        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-card name="..." SName="...">
            </x-card>

            <x-card name="..." SName="...">
            </x-card>

            <x-card name="..." SName="...">
            </x-card>
        </div> --}}
    </section>


</x-layout>
