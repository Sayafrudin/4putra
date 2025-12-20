@props(['gambar', 'name', 'SName'])
<div class="max-w-80 bg-black text-white">
    <div class="relative -mt-px overflow-hidden">
        @if (isset($gambar))
            <img src="{{ $gambar }}"
                class="h-67.5 w-62.5 hover:scale-105 transition-all duration-300 object-cover object-top">
        @else
            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?q=80&w=600&h=600&auto=format&fit=crop"
                alt="Default Image"
                class="h-67.5 w-62.5 hover:scale-105 transition-all duration-300 object-cover object-top">
        @endif
        <div class="absolute bottom-0 z-10 h-30 w-full bg-linear-to-t pointer-events-none from-black to-transparent">
        </div>
    </div>
    <div class="px-4 pb-6 text-center">
        <p class="mt-4 text-lg font-bold">{{ $name }}</p>
        <p class="text-sm font-medium text-[#E62C37]">
            {{ $SName }}</p>
    </div>
</div>
