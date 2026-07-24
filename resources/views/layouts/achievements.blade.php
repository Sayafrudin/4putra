<x-site.layout>
    @foreach($achievements as $achievement)
    <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10">
        <div class="flex flex-col md:flex-row items-center justify-center gap-10 lg:gap-12 max-w-7xl mx-auto">
            
            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left">
                <div class="text-sm font-semibold text-gray-400 tracking-wider mb-2">
                    {{ $achievement->year }}
                </div>
                
                <h1 class="text-3xl leading-tight md:text-5xl md:leading-tight lg:text-6xl lg:leading-tight font-bold uppercase text-slate-800">
                    {{ app()->getLocale() == 'en' && $achievement->title_en ? $achievement->title_en : $achievement->title }}
                </h1>

                <div class="text-gray-700 mt-6 text-sm md:text-md leading-relaxed whitespace-pre-line">
                    {{ app()->getLocale() == 'en' && $achievement->description_en ? $achievement->description_en : $achievement->description }}
                </div>
                
                <p class="text-xs text-gray-400 mt-4">
                    Surabaya, {{ \Carbon\Carbon::parse($achievement->date)->translatedFormat('d F Y') }}
                </p>
            </div>

            <div class="flex-1 lg:flex-none lg:w-5/12 flex flex-col items-center md:items-end gap-4 relative">
                
                <div class="w-full max-w-md overflow-hidden shadow-xl aspect-[4/3] relative group rounded-lg">
                    @if($achievement->images->isNotEmpty())
                        @php $firstImg = str_starts_with($achievement->images->first()->image_path, 'http') ? $achievement->images->first()->image_path : asset('storage/achievements/' . $achievement->images->first()->image_path); @endphp
                        <img src="{{ $firstImg }}" 
                             alt="{{ $achievement->title }}"
                             class="w-full h-full object-cover transition-all duration-500 hover:scale-105">
                    @else
                        <img src="{{ asset('img/placeholder.jpg') }}" alt="No Image" class="w-full h-full object-cover">
                    @endif
                    <div class="absolute inset-0 ring-1 ring-black/5 pointer-events-none"></div>
                </div>

                @if($achievement->images->count() > 1)
                <div class="flex flex-wrap gap-2 mt-2 justify-center md:justify-end max-w-md">
                    @foreach($achievement->images as $index => $img)
                        @php $thumbUrl = str_starts_with($img->image_path, 'http') ? $img->image_path : asset('storage/achievements/' . $img->image_path); @endphp
                        <div class="w-16 h-12 overflow-hidden rounded border border-gray-200 shadow-sm cursor-pointer hover:opacity-80">
                            <img src="{{ $thumbUrl }}" 
                                 alt="Galeri {{ $achievement->title }}" 
                                 class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
                @endif
                
            </div>
        </div>
    </section>
    @endforeach

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

        <x-site.divider>{{ __('about.team_devide') }}</x-site.divider>

        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-site.card gambar="{{ asset('img/manager.png') }}" name="Rachmad Hidayat" SName="{{ __('about.team_role3') }}"></x-site.card>
            <x-site.card gambar="{{ asset('img/direktur.png') }}" name="Dedy Murya Budi, SE" SName="{{ __('about.team_role1') }}"></x-site.card>
            <x-site.card gambar="{{ asset('img/komisaris.png') }}" name="Syafrudin Hendra Lumanto" SName="{{ __('about.team_role2') }}"></x-site.card>
        </div>
    </section>
    
    <x-site.whatsapp />
</x-site.layout>