@props(['type' => 'table'])

@if($type === 'table')
    {{-- Skeleton untuk tabel --}}
    <div class="w-full overflow-hidden bg-[#1e2530] border border-gray-800 shadow-sm mb-10 animate-pulse">
        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-400 bg-[#151a22] border-b border-gray-800">
                        <th class="px-6 py-4"><div class="h-3 bg-gray-700 rounded w-20"></div></th>
                        <th class="px-6 py-4 w-28"><div class="h-3 bg-gray-700 rounded w-16"></div></th>
                        <th class="px-6 py-4 w-36"><div class="h-3 bg-gray-700 rounded w-24"></div></th>
                        <th class="px-6 py-4 w-40"><div class="h-3 bg-gray-700 rounded w-20 ml-auto"></div></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @for($i = 0; $i < 5; $i++)
                        <tr class="border-b border-gray-800">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 bg-gray-700 rounded"></div>
                                    <div class="space-y-2">
                                        <div class="h-3 bg-gray-700 rounded w-32"></div>
                                        <div class="h-2 bg-gray-700 rounded w-48"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4"><div class="h-5 bg-gray-700 rounded w-12"></div></td>
                            <td class="px-6 py-4"><div class="h-3 bg-gray-700 rounded w-20"></div></td>
                            <td class="px-6 py-4 text-right"><div class="h-6 bg-gray-700 rounded w-16 ml-auto"></div></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
@elseif($type === 'card')
    {{-- Skeleton untuk card --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 animate-pulse">
        @for($i = 0; $i < 6; $i++)
            <div class="bg-[#1e2530] border border-gray-800 rounded-lg p-4 space-y-3">
                <div class="h-4 bg-gray-700 rounded w-3/4"></div>
                <div class="h-3 bg-gray-700 rounded w-1/2"></div>
                <div class="h-8 bg-gray-700 rounded w-full"></div>
            </div>
        @endfor
    </div>
@elseif($type === 'stat')
    {{-- Skeleton untuk statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6 animate-pulse">
        @for($i = 0; $i < 4; $i++)
            <div class="bg-[#1e2530] border border-gray-800 rounded-lg p-4 space-y-2">
                <div class="h-3 bg-gray-700 rounded w-20"></div>
                <div class="h-6 bg-gray-700 rounded w-12"></div>
            </div>
        @endfor
    </div>
@elseif($type === 'list')
    {{-- Skeleton untuk list --}}
    <div class="space-y-3 animate-pulse">
        @for($i = 0; $i < 8; $i++)
            <div class="flex items-center gap-3 p-3 bg-[#1e2530] border border-gray-800 rounded-lg">
                <div class="w-10 h-10 bg-gray-700 rounded-full"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 bg-gray-700 rounded w-1/3"></div>
                    <div class="h-2 bg-gray-700 rounded w-1/2"></div>
                </div>
            </div>
        @endfor
    </div>
@endif