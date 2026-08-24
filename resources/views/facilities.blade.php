<x-site.layout>
    @push('styles')
    <style>[x-cloak] { display: none !important; }</style>
    @endpush

    @php
        $isEn = app()->getLocale() === 'en';

        $transform = function ($url, $transformation) {
            return str_starts_with($url, 'http')
                ? str_replace('/upload/', '/upload/'.$transformation.'/', $url)
                : asset('storage/facilities/'.$url);
        };

        $parseEmbed = function (?string $url): ?string {
            $url = trim((string) $url);
            if ($url === '') {
                return null;
            }
            if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $url, $m)) {
                return 'https://www.youtube.com/embed/'.$m[1];
            }
            if (preg_match('/drive\.google\.com\/file\/d\/([^\/\?]+)/', $url, $m)) {
                return 'https://drive.google.com/file/d/'.$m[1].'/preview';
            }
            if (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $url, $m)) {
                return 'https://player.vimeo.com/video/'.$m[1];
            }
            if (preg_match('/dailymotion\.com\/video\/([^_\?]+)/', $url, $m)) {
                return 'https://www.dailymotion.com/embed/video/'.$m[1];
            }

            return null;
        };

        $feed = $facilities->map(function ($f) use ($isEn, $transform, $parseEmbed) {
            $description = $isEn && $f->description_en ? $f->description_en : $f->description;

            return [
                'title' => $isEn && $f->title_en ? $f->title_en : $f->title,
                'description' => $description,
                'excerpt' => Str::limit(strip_tags($description), 110),
                'cat' => $f->category,
                'catLabel' => $isEn && $f->category_en ? $f->category_en : $f->category,
                'video' => $parseEmbed($f->video_url),
                'thumbs' => collect($f->images ?? [])
                    ->map(fn ($u) => $transform($u, 'w_600,c_fill,q_auto,f_auto'))
                    ->values()
                    ->all(),
                'full' => collect($f->images ?? [])
                    ->map(fn ($u) => $transform($u, 'w_1600,q_auto,f_auto'))
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        // Urutan kategori sesuai kemunculan data (kanonik ID untuk filter)
        $categories = collect($feed)->pluck('cat')->unique()->values()->all();
    @endphp

    <section class="w-full px-6 md:px-12 lg:px-16 pb-24 pt-6">
        <div class="max-w-7xl mx-auto">

            {{-- Header halaman --}}
            <header class="mb-10 text-center">
                <h1 class="text-4xl font-bold tracking-tight text-slate-800 dark:text-slate-100">
                    {{ __('facilities.title_prefix') }}
                    <span class="font-medium text-[#E62C37]">{{ __('facilities.title_suffix') }}</span>
                </h1>
                <p class="mt-3 max-w-2xl mx-auto text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ __('facilities.hero_desc') }}
                </p>
            </header>

            <div x-data="{
                    items: @js($feed),
                    cat: 'all',
                    open: false,
                    idx: 0,
                    cur: 0,
                    openAt(i) { this.idx = i; this.cur = 0; this.open = true; document.documentElement.style.overflow = 'hidden'; this.warm(1) },
                    close() { this.open = false; document.documentElement.style.overflow = '' },
                    prev() { const n = this.items[this.idx].full.length; this.cur = (this.cur - 1 + n) % n; this.warm(-1) },
                    next() { const n = this.items[this.idx].full.length; this.cur = (this.cur + 1) % n; this.warm(1) },
                    warm(d) { const f = this.items[this.idx].full; if (f.length < 2) return; new Image().src = f[(this.cur + d + f.length) % f.length] }
                }">

                {{-- Filter pills kategori --}}
                <div class="flex flex-wrap justify-center items-center gap-x-2 gap-y-2.5 mb-10" role="group" aria-label="Filter kategori">
                    <button type="button" @click="cat = 'all'"
                        class="px-4 py-2 rounded-full text-sm font-semibold border transition-all duration-200"
                        :class="cat === 'all'
                            ? 'bg-[#E62C37] border-[#E62C37] text-white shadow-md'
                            : 'bg-transparent border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#E62C37] hover:text-[#E62C37]'">
                        {{ __('facilities.all') }}
                    </button>
                    @foreach ($categories as $c)
                        <button type="button" @click="cat = '{{ $c }}'"
                            class="px-4 py-2 rounded-full text-sm font-semibold border transition-all duration-200"
                            :class="cat === '{{ $c }}'
                                ? 'bg-[#E62C37] border-[#E62C37] text-white shadow-md'
                                : 'bg-transparent border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#E62C37] hover:text-[#E62C37]'">
                            {{ collect($feed)->first(fn ($i) => $i['cat'] === $c)['catLabel'] }}
                        </button>
                    @endforeach
                </div>

                {{-- Categorized grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7">
                    @forelse($feed as $item)
                        <article x-show="cat === 'all' || cat === '{{ $item['cat'] }}'"
                            @click="openAt({{ $loop->index }})"
                            class="group cursor-pointer bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/70 rounded-xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-gray-900">
                                @if (!empty($item['thumbs']))
                                    <img src="{{ $item['thumbs'][0] }}" loading="lazy" decoding="async"
                                        alt="Fasilitas {{ $item['title'] }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @endif
                                <span
                                    class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-gray-950/70 backdrop-blur-sm text-[11px] font-bold uppercase tracking-wider text-white">
                                    {{ $item['catLabel'] }}
                                </span>
                            </div>
                            <div class="p-5">
                                <h2
                                    class="text-base sm:text-lg font-bold uppercase leading-snug text-gray-900 dark:text-white group-hover:text-[#E62C37] transition-colors duration-300">
                                    {{ $item['title'] }}
                                </h2>
                                <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ $item['excerpt'] }}
                                </p>
                                <span
                                    class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-[#E62C37]">
                                    {{ __('facilities.view_detail') }}
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                                    </svg>
                                </span>
                            </div>
                        </article>
                    @empty
                        <div
                            class="col-span-full text-center py-20 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                            <div
                                class="mx-auto w-16 h-16 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-[#E62C37]">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                </svg>
                            </div>
                            <p class="mt-5 text-gray-500 dark:text-gray-400">{{ __('facilities.empty') }}</p>
                        </div>
                    @endforelse
                </div>

                {{-- ===================== SLIDE-OVER PANEL ===================== --}}
                <div x-cloak x-show="open" role="dialog" aria-modal="true"
                    class="fixed inset-0 z-[90]"
                    @keydown.escape.window="close()">
                    {{-- Backdrop --}}
                    <div class="absolute inset-0 bg-gray-950/70 backdrop-blur-sm"
                        x-show="open"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                        @click="close()"></div>

                    {{-- Panel drawer kanan --}}
                    <aside
                        class="absolute inset-y-0 right-0 w-full max-w-xl bg-white dark:bg-[#151a22] border-l border-gray-200 dark:border-gray-700 shadow-2xl flex flex-col"
                        x-show="open"
                        x-transition:enter="transform transition ease-out duration-300"
                        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in duration-200"
                        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

                        <template x-if="open && items[idx]">
                            <div class="flex flex-col h-full">
                                {{-- Header panel --}}
                                <div
                                    class="shrink-0 flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700/70">
                                    <div class="min-w-0">
                                        <span
                                            class="inline-flex px-2.5 py-1 rounded-full bg-red-50 dark:bg-red-900/20 text-[#E62C37] text-[11px] font-bold uppercase tracking-wider"
                                            x-text="items[idx].catLabel"></span>
                                        <h3
                                            class="mt-2 text-xl font-bold uppercase leading-snug text-gray-900 dark:text-white"
                                            x-text="items[idx].title"></h3>
                                    </div>
                                    <button type="button" @click="close()" aria-label="Close"
                                        class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 dark:bg-white/10 hover:bg-[#E62C37] hover:text-white text-gray-600 dark:text-white text-2xl leading-none transition-colors">
                                        &times;
                                    </button>
                                </div>

                                {{-- Isi panel: scrollable --}}
                                <div class="flex-1 overflow-y-auto overscroll-contain px-6 py-5">
                                    {{-- Video player (lazy: hanya mount saat panel aktif) --}}
                                    <template x-if="items[idx].video">
                                        <div class="aspect-video overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-black mb-6">
                                            <iframe :src="open ? items[idx].video : ''" loading="lazy"
                                                class="w-full h-full" frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen></iframe>
                                        </div>
                                    </template>

                                    {{-- Carousel galeri foto --}}
                                    <template x-if="items[idx].full.length > 0">
                                        <div class="relative mb-6">
                                            <div
                                                class="aspect-[4/3] overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900">
                                                <img :src="open ? items[idx].full[cur] : ''" loading="lazy" decoding="async"
                                                    :alt="items[idx].title + ' ' + (cur + 1)"
                                                    class="w-full h-full object-cover">
                                            </div>

                                            <template x-if="items[idx].full.length > 1">
                                                <div>
                                                    <button type="button" @click="prev()" aria-label="Sebelumnya"
                                                        class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center rounded-full bg-black/60 hover:bg-[#E62C37] text-white transition-colors">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                                                    </button>
                                                    <button type="button" @click="next()" aria-label="Berikutnya"
                                                        class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center rounded-full bg-black/60 hover:bg-[#E62C37] text-white transition-colors">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                                    </button>
                                                </div>
                                            </template>

                                            <span
                                                class="absolute bottom-3 right-3 px-2.5 py-1 rounded-full bg-black/70 text-xs font-bold text-white"
                                                x-text="(cur + 1) + ' / ' + items[idx].full.length"></span>
                                        </div>
                                    </template>

                                    {{-- Strip thumbnail --}}
                                    <template x-if="items[idx].full.length > 1">
                                        <div class="flex gap-2 overflow-x-auto pb-1 mb-6">
                                            <template x-for="(img, ti) in items[idx].thumbs" :key="ti">
                                                <button type="button" @click="cur = ti"
                                                    class="shrink-0 w-16 h-16 rounded border-2 overflow-hidden transition-colors"
                                                    :class="cur === ti ? 'border-[#E62C37]' : 'border-transparent opacity-60 hover:opacity-100'">
                                                    <img :src="open ? img : ''" loading="lazy" decoding="async" class="w-full h-full object-cover" alt="">
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Deskripsi --}}
                                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300 whitespace-pre-line"
                                        x-text="items[idx].description"></p>
                                </div>
                            </div>
                        </template>
                    </aside>
                </div>
            </div>
        </div>
    </section>
    <x-site.whatsapp />
</x-site.layout>
