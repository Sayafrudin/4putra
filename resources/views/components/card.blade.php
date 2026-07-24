@props(['gambar', 'name', 'SName'])

<div class="w-72 bg-black text-white overflow-hidden flex flex-col shadow-lg group">

    <div class="relative w-full aspect-[4/5] overflow-hidden">
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

        <div
            class="absolute bottom-0 z-10 h-2/3 w-full bg-gradient-to-t from-black via-black/20 to-transparent pointer-events-none">
        </div>
    </div>

    <div class="px-4 pb-5 pt-2 text-center z-20 relative bg-black flex-1 flex flex-col justify-end">
        <p class="text-xl font-bold leading-tight text-white">{{ $name }}</p>
        <p class="text-sm font-medium text-[#E62C37] mt-1 uppercase tracking-wide">
            {{ $SName }}
        </p>
    </div>
</div>