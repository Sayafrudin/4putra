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
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
            </div>

            <h1 class="mt-6 text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                {{ __('daily.title') }}
            </h1>
            <p class="mt-4 text-gray-600 dark:text-gray-400 leading-relaxed max-w-xl mx-auto">
                {{ __('daily.desc') }}
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
