<x-site.layout>
    <section class="min-h-[60vh] flex items-center justify-center px-6 py-16">
        <div class="max-w-2xl mx-auto text-center">

            {{-- Badge status --}}
            <div
                class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-red-50 dark:bg-red-900/30 border border-[#E62C37]/20 text-[#E62C37] text-sm font-semibold">
                <span class="relative flex size-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#E62C37] opacity-75"></span>
                    <span class="relative inline-flex rounded-full size-2 bg-[#E62C37]"></span>
                </span>
                {{ __('page.coming_soon') }}
            </div>

            {{-- Ilustrasi ikon --}}
            <div class="mx-auto mt-8 w-20 h-20 rounded-full bg-red-50 dark:bg-red-900/30 flex items-center justify-center text-[#E62C37]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-10 h-10">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
            </div>

            <h1 class="mt-6 text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                {{ __('facilities.title') }}
            </h1>
            <p class="mt-4 text-gray-600 dark:text-gray-400 leading-relaxed max-w-xl mx-auto">
                {{ __('facilities.desc') }}
            </p>

            {{-- CTA --}}
            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <a href="/collections"
                    class="px-6 py-3 rounded-xl bg-[#E62C37] text-white text-sm font-semibold shadow-md hover:bg-[#c92530] transition-colors">
                    {{ __('page.cta_collections') }}
                </a>
                <a href="/"
                    class="px-6 py-3 rounded-xl bg-[#151a22] border border-gray-600 dark:border-gray-700 text-white text-sm font-semibold hover:bg-gray-700 transition-colors">
                    {{ __('page.cta_home') }}
                </a>
            </div>
        </div>
    </section>
    <x-site.whatsapp />
</x-site.layout>
