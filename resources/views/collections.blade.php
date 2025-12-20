<x-layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-10">
        <h1 class="text-3xl font-bold text-slate-800 text-center">Meet Our Bird <span
                class="font-medium text-[#E62C37]">Collections</span> </h1>
        <p class="text-gray-700 text-center">Koleksi Parrot di Aviary Kami</p>

        <x-divider>Conure</x-divider>

        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-card gambar="{{ asset('img/sunc.png') }}" name="Sun Conure" SName="Aratinga solstitialis">
            </x-card>

            <x-card gambar="{{ asset('img/goldc.png') }}" name="Golden Conure" SName="Guaruba guarouba">
            </x-card>

            <x-card gambar="{{ asset('img/pata.png') }}" name="Patagonian" SName="Cyanoliseus patagonus">
            </x-card>

        </div>
    </section>

    <x-divider>Macaw</x-divider>

    <section class="w-full px-6 md:px-12 lg:px-16 pb-10">
        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-card gambar="{{ asset('img/verde.png') }}" name="Verde Macaw" SName="Ara ambiguus">
            </x-card>
            <x-card gambar="{{ asset('img/buffon1.png') }}" name="Buffon Macaw" SName="Ara ambiguus">
            </x-card>
            <x-card gambar="{{ asset('img/scarlet.png') }}" name="Scarlet Macaw" SName="Ara macao">
            </x-card>
            <x-card gambar="{{ asset('img/gw.png') }}" name="Greenwing Macaw" SName="Ara chloropterus">
            </x-card>
            <x-card gambar="{{ asset('img/catalina.png') }}" name="Catalina Macaw" SName="Ara ararauna">
            </x-card>
            <x-card gambar="{{ asset('img/miligold.png') }}" name="Miligold Macaw" SName="Ara militaris">
            </x-card>
        </div>
    </section>
</x-layout>
