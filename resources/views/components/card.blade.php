@props(['gambar', 'name', 'SName'])

<div class="w-72 bg-black text-white overflow-hidden flex flex-col shadow-lg group">

    <div class="relative w-full aspect-[4/5] overflow-hidden">
        @if (isset($gambar))
            <img src="{{ $gambar }}"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-all duration-500">
        @else
            <img src="https://images.unsplash.com/photo-1534030347209-467a5b0ad3e6?w=500&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTl8fHBvdHJhaXR8ZW58MHwxfDB8fHww"
                alt="Default Image"
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
