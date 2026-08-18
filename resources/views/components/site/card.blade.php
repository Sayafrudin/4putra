@props(['gambar', 'name', 'SName'])

@php
    // Buat URL gambar resolusi tinggi untuk lightbox
    $gambarFull = $gambar;
    if ($gambar && str_contains($gambar, '/upload/')) {
        // Hapus transformasi Cloudinary (w_400,c_fill,q_auto,f_auto) untuk dapat versi full
        $gambarFull = preg_replace('#/upload/[^/]+/#', '/upload/', $gambar);
    }
@endphp

<div class="w-72 bg-black text-white overflow-hidden flex flex-col shadow-lg group"
    x-data="{ showLightbox: false }">

    <div class="relative w-full aspect-[4/5] overflow-hidden cursor-pointer"
        @click="showLightbox = true">
        @if (isset($gambar))
            <img src="{{ $gambar }}"
                alt="{{ $name }}"
                loading="lazy"
                decoding="async"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-all duration-500">
        @else
            <div class="w-full h-full bg-gray-800 flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
        @endif

        <div class="absolute bottom-0 z-10 h-2/3 w-full bg-gradient-to-t from-black via-black/20 to-transparent pointer-events-none">
        </div>
    </div>

    <div class="px-4 pb-5 pt-2 text-center z-20 relative bg-black flex-1 flex flex-col justify-end">
        <p class="text-xl font-bold leading-tight text-white">{{ $name }}</p>
        <p class="text-sm font-medium text-[#E62C37] mt-1 uppercase tracking-wide">
            {{ $SName }}
        </p>
    </div>

    {{-- Lightbox Modal --}}
    <template x-if="showLightbox">
        <div class="fixed inset-0 z-[10000] bg-black/90 overflow-auto"
            @click.self="showLightbox = false"
            @keydown.escape.window="showLightbox = false">
            <button @click="showLightbox = false"
                class="fixed top-4 right-4 text-white/70 hover:text-white transition-colors z-10">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <div class="min-h-screen flex items-center justify-center p-4">
                <img src="{{ $gambarFull }}" alt="{{ $name }}"
                    class="block rounded shadow-2xl"
                    style="max-width: none;"
                    @click.stop>
            </div>
        </div>
    </template>
</div>