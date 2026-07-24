<a {{ $attributes }} class=" nav-link group flex flex-col gap-0.5 text-black">
    {{ $slot }}
    <div class="{{ $active ? 'w-full' : 'w-0' }} line-indicator bg-[#E62C37] h-0.5 w-0 group-hover:w-full transition-all duration-300"
        aria-current="{{ $active ? 'page' : 'false' }}">
    </div>
</a>
