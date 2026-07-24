<x-site.layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-10 pt-10">
        <h1 class="text-4xl font-bold text-slate-800 text-center">
            {{ __('collections.title_prefix') }}
            <span class="font-medium text-[#E62C37]">
                {{ __('collections.title_suffix') }}
            </span>
        </h1>

        <p class="text-gray-700 text-center mt-3">
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

            <div class="flex flex-wrap items-center justify-center gap-10">
                @foreach ($items as $item)
                    <x-card
                        gambar="{{ $item->image_path ? (str_starts_with($item->image_path, 'http') ? $item->image_path : asset('storage/collections/' . $item->image_path)) : asset('img/placeholder.jpg') }}"
                        name="{{ app()->getLocale() == 'en' && $item->name_en ? $item->name_en : $item->name }}"
                        SName="{{ $item->scientific_name ?: '' }}">
                    </x-card>
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