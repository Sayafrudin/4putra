@props(['gambar', 'name', 'SName'])

<div class="w-72 rounded-2xl overflow-hidden shadow-lg group">

    <div class="relative w-full aspect-[4/5] rounded-2xl overflow-hidden">
        @if (isset($gambar))
            <img src="{{ $gambar }}"
                alt="{{ $name }}"
                loading="lazy"
                decoding="async"
                onclick="zoomMedia(this.src)"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-all duration-500 cursor-pointer">
        @else
            <img src="{{ asset('img/placeholder.jpg') }}"
                alt="Default Image"
                loading="lazy"
                decoding="async"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-all duration-500">
        @endif

        {{-- Floating frosted glass info agar foto burung terlihat penuh --}}
        <div class="absolute bottom-2.5 inset-x-2.5 z-10 rounded-xl bg-black/85 border border-white/10 p-2.5 text-center shadow-lg pointer-events-none">
            <p class="text-white font-bold text-sm tracking-wide">{{ $name }}</p>
            <p class="text-red-400 font-semibold text-xs tracking-wider uppercase">{{ $SName }}</p>
        </div>
    </div>
</div>