<x-layout>
    <section class="w-full px-6 md:px-12 lg:px-16 pb-10">
        <h1 class="text-3xl font-bold text-slate-800 text-center">Meet Our Bird <span
                class="font-medium text-[#E62C37]">Collections</span> </h1>
        <p class="text-slate-500 text-center">Koleksi Parrot di Aviary Kami</p>

        <div class="flex w-full max-w-7xl mx-auto items-center rounded-full mt-8 mb-8">
            <div class="flex-1 h-px bg-linear-to-r from-transparent to-gray-500"></div>

            <span class="text-black text-2xl font-semibold leading-8 px-8 py-3">Conure</span>

            <div class="flex-1 h-px bg-linear-to-r from-gray-500 to-transparent"></div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-10">
            <x-card>
                <x-slot:image>
                    <img src="https://img.sanishtech.com/u/a7ad485e6d61e81a397bd88edd29a569.png" alt="Sun Conure"
                        class="h-[270px] w-full rounded-2xl hover:scale-105 transition-all duration-300 object-cover object-top">
                </x-slot:image>
                <x-slot:name>Sun Conure</x-slot:name>
                <x-slot:SName>Aratinga solstitialis</x-slot:SName>
            </x-card>
            <x-card>
                <x-slot:image>
                    <img src="https://img.sanishtech.com/u/2ab73a83ab9c1edde91b4c76bf584509.png" alt="Sun Conure"
                        class="h-[270px] w-full rounded-2xl hover:scale-105 transition-all duration-300 object-cover object-top">
                </x-slot:image>
                <x-slot:name>Golden Conure</x-slot:name>
                <x-slot:SName>Guaruba guarouba</x-slot:SName>
            </x-card>
            <x-card>
                <x-slot:image>
                    <img src="https://img.sanishtech.com/u/e0dc99105e97d921e3760437601a9e03.png" alt="Sun Conure"
                        class="h-[270px] w-full rounded-2xl hover:scale-105 transition-all duration-300 object-cover object-top">
                </x-slot:image>
                <x-slot:name>Patagonian</x-slot:name>
                <x-slot:SName>Cyanoliseus patagonus</x-slot:SName>
            </x-card>
        </div>
    </section>
</x-layout>
