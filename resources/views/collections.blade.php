<x-site.layout>
    @php
        $isEn = app()->getLocale() == 'en';
        $locName = fn ($i) => $isEn && $i->name_en ? $i->name_en : $i->name;
        $imgUrl = fn ($path) => $path
            ? (str_starts_with($path, 'http')
                ? str_replace('/upload/', '/upload/w_600,c_fill,q_auto,f_auto/', $path)
                : asset('storage/collections/' . $path))
            : asset('img/placeholder.jpg');
        $variantData = collect($collections)->flatten()
            ->filter(fn ($i) => $i->variants->isNotEmpty())->values()
            ->map(fn ($i) => [
                'id' => (string) $i->id,
                'name' => $locName($i),
                'scientific' => $i->scientific_name ?: '',
                'variants' => $i->variants->map(fn ($v) => [
                    'name' => $locName($v),
                    'image' => $imgUrl($v->image_path),
                ])->all(),
            ])->all();
    @endphp

    <section class="w-full px-6 md:px-12 lg:px-16 pb-10 pt-10" x-data="{
            items: @js($variantData),
            open: false,
            idx: 0,
            openAt(id) { const i = this.items.findIndex(it => it.id === id); if (i < 0) return; this.idx = i; this.open = true; document.documentElement.style.overflow = 'hidden' },
            close() { this.open = false; document.documentElement.style.overflow = '' }
        }">
        <h1 class="text-4xl font-bold text-slate-800 dark:text-slate-100 text-center">
            {{ __('collections.title_prefix') }}
            <span class="font-medium text-[#E62C37]">
                {{ __('collections.title_suffix') }}
            </span>
        </h1>

        <p class="text-gray-700 dark:text-gray-300 text-center mt-3">
            {{ __('collections.desc') }}
        </p>

        @forelse($collections as $category => $items)
            @php
                $catName =
                    app()->getLocale() == 'en' && $items->first()->category_en
                        ? $items->first()->category_en
                        : $category;
            @endphp
            <x-site.divider>{{ $catName }}</x-site.divider>

            <div class="flex flex-wrap items-start justify-center gap-10">
                @foreach ($items as $item)
                    @php
                        $imgUrl = $item->image_path
                            ? (str_starts_with($item->image_path, 'http')
                                ? str_replace('/upload/', '/upload/w_400,c_fill,q_auto,f_auto/', $item->image_path)
                                : asset('storage/collections/' . $item->image_path))
                            : asset('img/placeholder.jpg');
                        $name = app()->getLocale() == 'en' && $item->name_en ? $item->name_en : $item->name;
                        $variantCount = $item->variants->count();
                    @endphp

                    <div class="flex flex-col items-center">
                        @if ($variantCount > 0)
                            {{-- Card induk: klik membuka modal varian fullscreen, tanpa navigasi --}}
                            <button type="button"
                                @click="openAt('{{ $item->id }}')"
                                aria-haspopup="dialog"
                                aria-label="{{ __('collections.variant_count', ['n' => $variantCount]) }} - {{ $name }}"
                                class="relative w-72 rounded-2xl overflow-hidden shadow-lg group text-left cursor-pointer hover:-translate-y-1 hover:shadow-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E62C37] transition-all duration-300">
                                <div class="relative w-full aspect-[4/5] rounded-2xl overflow-hidden">
                                    <img src="{{ $imgUrl }}" alt="{{ $name }}" loading="lazy" decoding="async"
                                        class="w-full h-full object-cover object-top group-hover:scale-105 transition-all duration-500">
                                    <div class="absolute bottom-2.5 inset-x-2.5 z-10 rounded-xl bg-black/85 border border-white/10 p-2.5 text-center shadow-lg pointer-events-none">
                                        <p class="text-white font-bold text-sm tracking-wide">{{ $name }}</p>
                                        <p class="text-red-400 font-semibold text-xs tracking-wider uppercase">{{ $item->scientific_name ?: '' }}</p>
                                    </div>
                                    <span class="absolute top-2.5 right-2.5 z-10 px-2.5 py-1 rounded-full bg-[#E62C37]/90 text-white text-xs font-bold shadow-lg">
                                        {{ trans_choice('collections.variant_count', $variantCount, ['n' => $variantCount]) }}
                                    </span>
                                    <svg class="absolute bottom-3 right-3 z-10 w-5 h-5 text-white drop-shadow"
                                        fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                    </svg>
                                </div>
                            </button>
                        @else
                            <x-card gambar="{{ $imgUrl }}" name="{{ $name }}" SName="{{ $item->scientific_name ?: '' }}"></x-card>
                        @endif
                    </div>
                @endforeach
            </div>
        @empty
            <section class="w-full px-6 py-20 text-center">
                <p class="text-gray-500 text-lg">Belum ada koleksi burung yang tersedia.</p>
            </section>
        @endforelse

        {{-- ===================== MODAL VARIAN (ala Daily Activities) ===================== --}}
        {{-- template x-if: nol render DOM saat tertutup, gambar varian baru dimuat saat dibuka --}}
        <div x-cloak x-show="open" role="dialog" aria-modal="true"
            class="fixed inset-0 z-[90]"
            @keydown.escape.window="close()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-gray-950/95" @click="close()"></div>

            <template x-if="open && items[idx]">
                <div class="relative h-full flex flex-col">
                    {{-- Header modal --}}
                    <div class="shrink-0 flex items-start justify-between gap-4 px-5 sm:px-8 pt-5 pb-4 border-b border-white/10">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[#ff6b73]"
                                x-show="items[idx].scientific" x-text="items[idx].scientific"></p>
                            <h3 class="mt-1 text-lg sm:text-2xl font-bold uppercase leading-snug text-white"
                                x-text="items[idx].name"></h3>
                        </div>
                        <button type="button" @click="close()" aria-label="Tutup"
                            class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-[#E62C37] text-white text-2xl leading-none transition-colors">
                            &times;
                        </button>
                    </div>

                    {{-- Body: grid varian gambar besar (seukuran card induk) --}}
                    <div class="flex-1 overflow-y-auto overscroll-contain px-5 sm:px-8 py-6">
                        <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <template x-for="(v, vi) in items[idx].variants" :key="vi">
                                <div class="rounded-2xl overflow-hidden shadow-lg bg-white dark:bg-[#151a22] border border-gray-200 dark:border-gray-700 group">
                                    <div class="w-full aspect-[4/5] overflow-hidden">
                                        <img :src="v.image" :alt="v.name" loading="lazy" decoding="async"
                                            @click="zoomMedia(v.image)"
                                            class="w-full h-full object-cover object-top cursor-pointer group-hover:scale-105 transition-all duration-500">
                                    </div>
                                    <p class="text-sm font-bold text-center py-2.5 px-2 text-gray-800 dark:text-gray-100" x-text="v.name"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </section>
    <x-site.whatsapp />
</x-site.layout>
