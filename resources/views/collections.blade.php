<x-layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-10 pt-10">
        <h1 class="text-4xl font-bold text-slate-800 text-center">
            {{ __('collections.title_prefix') }}
            <span class="font-medium text-[#E62C37]">
                {{ __('collections.title_suffix') }}
            </span>
        </h1>

        <p class="text-gray-700 text-center mt-3">
            {{ __('collections.desc') }}
        </p>

        <x-divider>{{ __('category.conure') }}</x-divider>

        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-card gambar="{{ asset('img/sunc.png') }}" name="{{ __('bird.sun_conure') }}" SName="Aratinga solstitialis">
            </x-card>

            <x-card gambar="{{ asset('img/goldc.png') }}" name="{{ __('bird.golden_conure') }}" SName="Guaruba guarouba">
            </x-card>

            <x-card gambar="{{ asset('img/pata.png') }}" name="{{ __('bird.patagonian') }}"
                SName="Cyanoliseus patagonus">
            </x-card>
        </div>
    </section>

    <x-divider>{{ __('category.macaw') }}</x-divider>

    <section class="w-full px-6 md:px-12 lg:px-16 pb-10">
        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-card gambar="{{ asset('img/verde.png') }}" name="{{ __('bird.verde_macaw') }}"
                SName="Ara ambiguus"></x-card>

            <x-card gambar="{{ asset('img/buffon1.png') }}" name="{{ __('bird.buffon_macaw') }}"
                SName="Ara ambiguus"></x-card>

            <x-card gambar="{{ asset('img/scarlet.png') }}" name="{{ __('bird.scarlet_macaw') }}"
                SName="Ara macao"></x-card>

            <x-card gambar="{{ asset('img/gw.png') }}" name="{{ __('bird.greenwing_macaw') }}"
                SName="Ara chloropterus"></x-card>

            <x-card gambar="{{ asset('img/catalina.png') }}" name="{{ __('bird.catalina_macaw') }}"
                SName="Ara ararauna"></x-card>

            <x-card gambar="{{ asset('img/miligold.png') }}" name="{{ __('bird.miligold_macaw') }}"
                SName="Ara militaris"></x-card>
        </div>
    </section>
    <x-whatsapp />
</x-layout>
