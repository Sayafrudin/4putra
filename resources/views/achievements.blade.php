<x-site.layout>
    @forelse($achievements as $year => $items)
        <x-site.divider>{{ $year }}</x-site.divider>

        @foreach ($items as $achievement)
            <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10">
                <div class="flex flex-col lg:flex-row items-start justify-between gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto">

                    <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left">
                        <h1 class="text-2xl leading-tight md:text-3xl md:leading-tight lg:text-3xl lg:leading-tight font-bold uppercase">
                            {{ app()->getLocale() == 'en' && $achievement->title_en ? $achievement->title_en : $achievement->title }}
                            @php
                                $highlight = app()->getLocale() == 'en' && $achievement->title_highlight_en
                                    ? $achievement->title_highlight_en
                                    : $achievement->title_highlight;
                            @endphp
                            @if ($highlight)
                                <span class="text-[#E62C37] font-normal">{{ $highlight }}</span>
                            @endif
                        </h1>

                        <div class="text-gray-700 mt-6 md:text-md leading-relaxed max-w-2xl whitespace-pre-line">
                            {{ app()->getLocale() == 'en' && $achievement->description_en ? $achievement->description_en : $achievement->description }}
                        </div>

                        @if ($achievement->external_link)
                            @php
                                $extLinks = $achievement->external_link;
                                if (is_string($extLinks)) { $extLinks = json_decode($extLinks, true) ?: [$extLinks]; }
                                if (!is_array($extLinks)) { $extLinks = [$extLinks]; }
                            @endphp
                            @foreach ($extLinks as $link)
                                @if ($link)
                                    <div class="mt-2">
                                        <a href="{{ $link }}" target="_blank"
                                            class="inline-flex items-center gap-2 text-[#E62C37] hover:underline text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                            Lihat Artikel Terkait {{ $loop->count > 1 ? ($loop->iteration) : '' }}
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        @endif

                        <p class="text-xs text-gray-400 mt-4">
                            Surabaya, {{ \Carbon\Carbon::parse($achievement->date)->translatedFormat('d F Y') }}
                        </p>
                    </div>

                    @php
                        $hasImages = $achievement->images->isNotEmpty();
                        $videoUrls = [];
                        if ($achievement->video_url) {
                            if (is_string($achievement->video_url)) {
                                $decoded = json_decode($achievement->video_url, true);
                                $videoUrls = is_array($decoded) ? $decoded : [$achievement->video_url];
                            } elseif (is_array($achievement->video_url)) {
                                $videoUrls = $achievement->video_url;
                            }
                        }
                        $hasVideo = $achievement->video_file || count($videoUrls) > 0;
                    @endphp

                    @if ($hasImages || $hasVideo)
                        <div class="w-full lg:w-5/12 flex flex-col space-y-4"
                            x-data="{ activeMedia: '{{ $hasVideo ? 'video-0' : 'img-0' }}' }">

                            {{-- Area utama: video atau gambar --}}
                            <div class="w-full aspect-[4/3] md:aspect-[5/4] bg-gray-100 overflow-hidden shadow-lg relative group shrink-0 border border-gray-100">
                                @if ($hasVideo)
                                    @if ($achievement->video_file)
                                        <div x-show="activeMedia === 'video-file'" x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-50" x-transition:enter-end="opacity-100"
                                            class="w-full h-full flex items-center justify-center bg-black">
                                            <video controls controlsList="nodownload noplaybackrate" oncontextmenu="return false" class="w-full h-full object-contain" preload="metadata">
                                                <source src="{{ asset('storage/achievements/videos/' . $achievement->video_file) }}">
                                            </video>
                                        </div>
                                    @endif

                                    @foreach ($videoUrls as $vIdx => $vUrl)
                                        @php
                                            // YouTube
                                            $ytId = '';
                                            if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $vUrl, $m)) { $ytId = $m[1]; }

                                            // Google Drive
                                            $gdriveId = '';
                                            if (preg_match('/drive\.google\.com\/file\/d\/([^\/\?]+)/', $vUrl, $m2)) { $gdriveId = $m2[1]; }
                                            if (!$gdriveId && preg_match('/drive\.google\.com\/.*[?&]id=([^&]+)/', $vUrl, $m2)) { $gdriveId = $m2[1]; }

                                            // Vimeo
                                            $vimeoId = '';
                                            if (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $vUrl, $m3)) { $vimeoId = $m3[1]; }

                                            // Dailymotion
                                            $dmId = '';
                                            if (preg_match('/dailymotion\.com\/video\/([^_\?]+)/', $vUrl, $m4)) { $dmId = $m4[1]; }
                                        @endphp
                                        <div x-show="activeMedia === 'video-{{ $vIdx }}'" x-transition:enter="transition ease-out duration-300"
                                            x-transition:enter-start="opacity-50" x-transition:enter-end="opacity-100"
                                            class="w-full h-full flex items-center justify-center bg-black absolute inset-0">
                                            @if ($ytId)
                                                <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $ytId }}"
                                                    frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                            @elseif ($gdriveId)
                                                <iframe class="w-full h-full" src="https://drive.google.com/file/d/{{ $gdriveId }}/preview"
                                                    frameborder="0" allow="autoplay" allowfullscreen></iframe>
                                            @elseif ($vimeoId)
                                                <iframe class="w-full h-full" src="https://player.vimeo.com/video/{{ $vimeoId }}"
                                                    frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                                            @elseif ($dmId)
                                                <iframe class="w-full h-full" src="https://www.dailymotion.com/embed/video/{{ $dmId }}"
                                                    frameborder="0" allowfullscreen></iframe>
                                            @else
                                                <a href="{{ $vUrl }}" target="_blank" class="text-white flex flex-col items-center gap-2">
                                                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                                                    <span class="text-sm">Tonton Video</span>
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif

                                @foreach ($achievement->images as $index => $img)
                                    @php $imgUrl = asset('storage/achievements/' . $img->image_path); @endphp
                                    <img x-show="activeMedia === 'img-{{ $index }}'" :src="'{{ $imgUrl }}'"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-50" x-transition:enter-end="opacity-100"
                                        class="w-full h-full object-cover object-center transition-all duration-500 absolute inset-0"
                                        alt="{{ $achievement->title }}">
                                @endforeach
                            </div>

                            {{-- Thumbnail bar --}}
                            <div class="w-full">
                                <div class="flex gap-2 overflow-x-auto pb-1 px-1 scrollbar-hide snap-x cursor-pointer md:justify-center">
                                    @if ($achievement->video_file)
                                        <div @click="activeMedia = 'video-file'"
                                            :class="activeMedia === 'video-file'
                                                ? 'border-[#E62C37] opacity-100 ring-2 ring-[#E62C37]/30'
                                                : 'border-transparent opacity-60 hover:opacity-100'"
                                            class="thumb shrink-0 w-16 h-12 md:w-20 md:h-16 bg-black border-2 transition-all duration-200 snap-start shadow-sm flex items-center justify-center rounded">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    @endif

                                    @if (!empty($videoUrls))
                                        @foreach ($videoUrls as $vIdx => $vUrl)
                                            <div @click="activeMedia = 'video-{{ $vIdx }}'"
                                                :class="activeMedia === 'video-{{ $vIdx }}'
                                                    ? 'border-[#E62C37] opacity-100 ring-2 ring-[#E62C37]/30'
                                                    : 'border-transparent opacity-60 hover:opacity-100'"
                                                class="thumb shrink-0 w-16 h-12 md:w-20 md:h-16 bg-black border-2 transition-all duration-200 snap-start shadow-sm flex items-center justify-center rounded">
                                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </div>
                                        @endforeach
                                    @endif

                                    @foreach ($achievement->images as $index => $img)
                                        @php $imgUrl = asset('storage/achievements/' . $img->image_path); @endphp
                                        <img @click="activeMedia = 'img-{{ $index }}'" src="{{ $imgUrl }}"
                                            :class="activeMedia === 'img-{{ $index }}'
                                                ? 'border-[#E62C37] opacity-100 ring-2 ring-[#E62C37]/30'
                                                : 'border-transparent opacity-60 hover:opacity-100'"
                                            class="thumb shrink-0 w-16 h-12 md:w-20 md:h-16 object-cover border-2 transition-all duration-200 snap-start shadow-sm"
                                            alt="Foto {{ $index + 1 }}">
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="w-full lg:w-5/12">
                            <div class="w-full aspect-[4/3] md:aspect-[5/4] bg-gray-100 overflow-hidden shadow-lg relative group shrink-0 border border-gray-100 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                </svg>
                            </div>
                        </div>
                    @endif

                </div>
            </section>
        @endforeach
    @empty
        <section class="w-full px-6 py-20 text-center">
            <p class="text-gray-500 text-lg">Belum ada pencapaian yang tersedia.</p>
        </section>
    @endforelse

    <x-site.whatsapp />
</x-site.layout>
