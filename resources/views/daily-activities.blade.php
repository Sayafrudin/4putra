<x-site.layout>
    @push('styles')
    <style>[x-cloak] { display: none !important; }</style>
    @endpush

    @php
        $isEn = app()->getLocale() === 'en';

        $transform = function ($url, $transformation) {
            return str_starts_with($url, 'http')
                ? str_replace('/upload/', '/upload/'.$transformation.'/', $url)
                : asset('storage/daily-activities/'.$url);
        };

        $feed = $activities->map(function ($a) use ($isEn, $transform) {
            $description = $isEn && $a->description_en ? $a->description_en : $a->description;

            return [
                'title' => $isEn && $a->title_en ? $a->title_en : $a->title,
                'description' => $description,
                'excerpt' => Str::limit(strip_tags($description), 170),
                'date' => \Carbon\Carbon::parse($a->activity_date)->translatedFormat('d F Y'),
                'thumbs' => collect($a->images ?? [])
                    ->map(fn ($u) => $transform($u, 'w_600,c_fill,q_auto,f_auto'))
                    ->values()
                    ->all(),
                'full' => collect($a->images ?? [])
                    ->map(fn ($u) => $transform($u, 'w_1600,q_auto,f_auto'))
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    @endphp

    <section class="w-full px-6 md:px-12 lg:px-16 pb-24 pt-6">
        <div class="max-w-7xl mx-auto">

            {{-- Header halaman --}}
            <header class="mb-12">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[#E62C37]/25 text-[#E62C37] text-xs font-bold uppercase tracking-wider">
                    {{ __('daily.page_badge') }}
                </div>
                <h1 class="mt-4 text-3xl md:text-4xl font-extrabold uppercase tracking-tight text-gray-900 dark:text-white">
                    {{ __('daily.hero_title') }}
                </h1>
                <p class="mt-3 max-w-2xl text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ __('daily.hero_desc') }}
                </p>
            </header>

            <div x-data="{
                    items: @js($feed),
                    open: false,
                    idx: 0,
                    cur: 0,
                    openAt(i) { this.idx = i; this.cur = 0; this.open = true; document.documentElement.style.overflow = 'hidden'; this.warm(1) },
                    close() { this.open = false; document.documentElement.style.overflow = '' },
                    prev() { const n = this.items[this.idx].full.length; this.cur = (this.cur - 1 + n) % n; this.warm(-1) },
                    next() { const n = this.items[this.idx].full.length; this.cur = (this.cur + 1) % n; this.warm(1) },
                    warm(d) { const f = this.items[this.idx].full; if (f.length < 2) return; new Image().src = f[(this.cur + d + f.length) % f.length] }
                }">

                <div class="space-y-8">
                    @forelse($feed as $item)
                        <article
                            @click="openAt({{ $loop->index }})"
                            class="group cursor-pointer bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/70 rounded-xl shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 overflow-hidden">
                            <div class="grid grid-cols-1 lg:grid-cols-[1fr_minmax(0,44%)] gap-5 lg:gap-12 p-5 sm:p-7 items-center">

                                {{-- Teks: tanggal + judul (mobile pertama, desktop kolom kiri) --}}
                                <div class="min-w-0">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 dark:bg-red-900/20 text-[#E62C37] text-xs font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        {{ $item['date'] }}
                                    </span>
                                    <h2 class="mt-3 text-xl sm:text-2xl font-bold uppercase leading-snug text-gray-900 dark:text-white group-hover:text-[#E62C37] transition-colors duration-300">
                                        {{ $item['title'] }}
                                    </h2>
                                    <p class="hidden lg:block mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                        {{ $item['excerpt'] }}
                                    </p>
                                    <span
                                        class="hidden lg:inline-flex mt-4 items-center gap-1.5 text-sm font-semibold text-[#E62C37]">
                                        {{ __('daily.view_gallery') }}
                                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </span>
                                </div>

                                {{-- Grid pratinjau gambar (maks 4, overlay +X) --}}
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach (array_slice($item['thumbs'], 0, 4) as $i => $thumb)
                                        <div
                                            class="relative aspect-[4/3] overflow-hidden rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-100 dark:bg-gray-900">
                                            <img src="{{ $thumb }}" loading="lazy" decoding="async"
                                                alt="Dokumentasi {{ $item['title'] }} {{ $i + 1 }}"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                            @if ($i === 3 && count($item['thumbs']) > 4)
                                                <div
                                                    class="absolute inset-0 bg-gray-950/70 flex items-center justify-center text-white text-xl font-bold">
                                                    +{{ count($item['thumbs']) - 4 }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Potongan deskripsi: tumpukan setelah grid foto di mobile --}}
                                <p class="lg:hidden text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                    {{ $item['excerpt'] }}
                                </p>
                            </div>
                        </article>
                    @empty
                        <div
                            class="text-center py-20 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                            <div
                                class="mx-auto w-16 h-16 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-[#E62C37]">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            </div>
                            <p class="mt-5 text-gray-500 dark:text-gray-400">{{ __('daily.empty') }}</p>
                        </div>
                    @endforelse
                </div>

                {{-- ===================== LIGHTBOX MODAL ===================== --}}
                <div x-cloak x-show="open" role="dialog" aria-modal="true"
                    class="fixed inset-0 z-[90]"
                    @keydown.escape.window="close()"
                    @keydown.arrow-left.prevent="prev()"
                    @keydown.arrow-right.prevent="next()"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="absolute inset-0 bg-gray-950/95 backdrop-blur-sm" @click="close()"></div>

                    <template x-if="open && items[idx]">
                        <div class="relative h-full flex flex-col">
                            {{-- Header modal --}}
                            <div
                                class="shrink-0 flex items-start justify-between gap-4 px-5 sm:px-8 pt-5 pb-4 border-b border-white/10">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-[#ff6b73]"
                                        x-text="items[idx].date"></p>
                                    <h3 class="mt-1 text-lg sm:text-2xl font-bold uppercase leading-snug text-white truncate"
                                        x-text="items[idx].title"></h3>
                                </div>
                                <button type="button" @click="close()" aria-label="Close"
                                    class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-[#E62C37] text-white text-2xl leading-none transition-colors">
                                    &times;
                                </button>
                            </div>

                            {{-- Body scrollable --}}
                            <div class="flex-1 overflow-y-auto overscroll-contain px-5 sm:px-8 py-5">
                                <div class="max-w-5xl mx-auto">

                                    {{-- Slider utama --}}
                                    <div class="relative">
                                        <div
                                            class="aspect-[16/10] sm:aspect-[16/9] overflow-hidden rounded-lg border border-white/10 bg-black/40">
                                            <img :src="open ? items[idx].full[cur] : ''" loading="lazy" decoding="async"
                                                :alt="items[idx].title + ' ' + (cur + 1)"
                                                class="w-full h-full object-contain">
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

                                    {{-- Strip thumbnail: pakai thumbs (w_600) yang sudah ter-cache dari grid kartu --}}
                                    <template x-if="items[idx].full.length > 1">
                                        <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                                            <template x-for="(img, ti) in items[idx].thumbs" :key="ti">
                                                <button type="button" @click="cur = ti"
                                                    class="shrink-0 w-16 h-16 rounded border-2 overflow-hidden transition-colors"
                                                    :class="cur === ti ? 'border-[#E62C37]' : 'border-transparent opacity-60 hover:opacity-100'">
                                                    <img :src="open ? img : ''" loading="lazy" decoding="async" class="w-full h-full object-cover" alt="">
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Deskripsi lengkap --}}
                                    <p class="mt-6 text-sm sm:text-base leading-relaxed text-gray-300 whitespace-pre-line"
                                        x-text="items[idx].description"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </section>
    <x-site.whatsapp />
</x-site.layout>
