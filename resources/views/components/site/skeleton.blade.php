@props(['type' => 'card'])

@if($type === 'card')
    {{-- Skeleton untuk card koleksi --}}
    <div class="w-72 bg-black overflow-hidden flex flex-col shadow-lg animate-pulse">
        <div class="relative w-full aspect-[4/5] bg-gray-800"></div>
        <div class="px-4 pb-5 pt-2 text-center flex-1 flex flex-col justify-end space-y-2">
            <div class="h-5 bg-gray-700 rounded w-3/4 mx-auto"></div>
            <div class="h-3 bg-gray-700 rounded w-1/2 mx-auto"></div>
        </div>
    </div>
@elseif($type === 'hero')
    {{-- Skeleton untuk hero section --}}
    <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10 md:pb-48 animate-pulse">
        <div class="flex flex-col md:flex-row items-center justify-center gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto">
            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left space-y-4">
                <div class="h-10 bg-gray-200 rounded w-3/4"></div>
                <div class="h-10 bg-gray-200 rounded w-1/2"></div>
                <div class="space-y-2 mt-6">
                    <div class="h-4 bg-gray-200 rounded w-full"></div>
                    <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                    <div class="h-4 bg-gray-200 rounded w-4/6"></div>
                </div>
                <div class="flex gap-3 mt-10">
                    <div class="h-9 w-28 rounded-full bg-gray-200"></div>
                    <div class="h-9 w-32 rounded-full bg-gray-200"></div>
                </div>
            </div>
            <div class="flex-1 flex justify-center md:justify-end">
                <div class="hidden md:block max-w-sm lg:max-w-md xl:max-w-lg aspect-square bg-gray-200 rounded-lg"></div>
            </div>
        </div>
    </section>
@elseif($type === 'achievement')
    {{-- Skeleton untuk achievement --}}
    <section class="w-full px-6 md:px-12 lg:px-16 pb-20 pt-10 animate-pulse">
        <div class="flex flex-col lg:flex-row items-start justify-between gap-10 md:gap-16 lg:gap-24 max-w-7xl mx-auto">
            <div class="flex flex-col items-center md:items-start flex-1 text-center md:text-left space-y-4">
                <div class="h-8 bg-gray-200 rounded w-3/4"></div>
                <div class="h-8 bg-gray-200 rounded w-1/2"></div>
                <div class="space-y-2 mt-6">
                    <div class="h-4 bg-gray-200 rounded w-full"></div>
                    <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                    <div class="h-4 bg-gray-200 rounded w-4/6"></div>
                    <div class="h-4 bg-gray-200 rounded w-3/6"></div>
                </div>
                <div class="h-3 bg-gray-200 rounded w-40 mt-4"></div>
            </div>
            <div class="w-full lg:w-5/12">
                <div class="w-full aspect-[4/3] md:aspect-[5/4] bg-gray-200 rounded-lg"></div>
                <div class="flex gap-2 mt-4">
                    <div class="w-16 h-12 bg-gray-200 rounded"></div>
                    <div class="w-16 h-12 bg-gray-200 rounded"></div>
                    <div class="w-16 h-12 bg-gray-200 rounded"></div>
                </div>
            </div>
        </div>
    </section>
@elseif($type === 'team')
    {{-- Skeleton untuk team card --}}
    <div class="w-72 bg-white overflow-hidden flex flex-col shadow-lg animate-pulse">
        <div class="relative w-full aspect-[4/5] bg-gray-200"></div>
        <div class="px-4 pb-5 pt-4 text-center space-y-2">
            <div class="h-5 bg-gray-200 rounded w-3/4 mx-auto"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2 mx-auto"></div>
        </div>
    </div>
@elseif($type === 'marquee')
    {{-- Skeleton untuk marquee --}}
    <div class="w-full lg:w-2/3 overflow-hidden h-[24rem] flex items-center animate-pulse [mask-image:linear-gradient(to_right,transparent,black_5%,black_95%,transparent)] [-webkit-mask-image:linear-gradient(to_right,transparent,black_5%,black_95%,transparent)]">
        <div class="flex gap-4">
            @for($i = 0; $i < 4; $i++)
                <div class="w-56 h-[20rem] bg-gray-200 rounded flex-shrink-0"></div>
            @endfor
        </div>
    </div>
@endif