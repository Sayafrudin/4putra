@props(['gambar', 'name', 'SName'])

<div class="w-72 overflow-hidden shadow-lg group">

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

        {{-- Strip info semi-transparan di dasar foto agar foto burung terlihat penuh --}}
        <div class="absolute inset-x-0 bottom-0 z-10 bg-black/40 backdrop-blur-md px-4 py-3 text-center pointer-events-none">
            <p class="text-xl font-bold leading-tight text-white">{{ $name }}</p>
            <p class="text-sm font-medium text-[#E62C37] mt-1 uppercase tracking-wide">
                {{ $SName }}
            </p>
        </div>
    </div>
</div>