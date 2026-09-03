<x-site.layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-10 pt-10">
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

            <div class="flex flex-wrap items-start justify-center gap-10" x-data="{ expanded: null }">
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
                            {{-- Card induk: klik toggle grid varian, tanpa navigasi --}}
                            <button type="button"
                                @click="expanded = expanded === '{{ $item->id }}' ? null : '{{ $item->id }}'"
                                :aria-expanded="expanded === '{{ $item->id }}' ? 'true' : 'false'"
                                aria-label="{{ __('collections.variant_count', ['n' => $variantCount]) }} - {{ $name }}"
                                class="relative w-72 rounded-2xl overflow-hidden shadow-lg group text-left cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E62C37]">
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
                                    <svg class="absolute bottom-3 right-3 z-10 w-5 h-5 text-white drop-shadow transition-transform duration-300 ease-in-out"
                                        :class="expanded === '{{ $item->id }}' ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                    </svg>
                                </div>
                            </button>

                            {{-- Expandable grid varian --}}
                            <div x-show="expanded === '{{ $item->id }}'" x-cloak
                                x-transition:enter="transition-all duration-300 ease-in-out"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition-all duration-200 ease-in-out"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="w-72 mt-4 grid grid-cols-2 gap-3">
                                @foreach ($item->variants as $variant)
                                    @php
                                        $vImg = $variant->image_path
                                            ? (str_starts_with($variant->image_path, 'http')
                                                ? str_replace('/upload/', '/upload/w_300,c_fill,q_auto,f_auto/', $variant->image_path)
                                                : asset('storage/collections/' . $variant->image_path))
                                            : asset('img/placeholder.jpg');
                                        $vName = app()->getLocale() == 'en' && $variant->name_en ? $variant->name_en : $variant->name;
                                    @endphp
                                    <div class="rounded-xl overflow-hidden shadow-md bg-white dark:bg-[#151a22] border border-gray-200 dark:border-gray-700">
                                        <img src="{{ $vImg }}" alt="{{ $vName }}" loading="lazy" decoding="async" onclick="zoomMedia(this.src)"
                                            class="w-full aspect-square object-cover object-top cursor-pointer hover:scale-105 transition-all duration-300">
                                        <p class="text-xs font-bold text-center py-2 px-1.5 text-gray-800 dark:text-gray-100">{{ $vName }}</p>
                                    </div>
                                @endforeach
                            </div>
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
    </section>
    <x-site.whatsapp />
</x-site.layout>