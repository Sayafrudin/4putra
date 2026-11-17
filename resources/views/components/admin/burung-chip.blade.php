@props(['burung', 'lokasiBadge' => null])

{{-- Chip responsif: di HP kartu lebar penuh, nama & gender terbaca utuh --}}
<div class="burung-chip group relative flex items-center gap-2 bg-gray-50 dark:bg-[#1e2530] border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-2 cursor-grab active:cursor-grabbing select-none w-full sm:w-auto"
    draggable="true"
    data-burung-id="{{ $burung->id }}"
    data-nama="{{ $burung->nama }}"
    data-kandang-id="{{ $burung->kandang_id }}"
    title="{{ $burung->nama }} - {{ $burung->spesies }} ({{ $burung->fase_label }}){{ $burung->gender_label ? ' · '.$burung->gender_label : '' }}">
    <span class="w-2 h-2 rounded-full bg-[#E62C37] shrink-0"></span>
    <div class="min-w-0 grow">
        <p class="text-xs font-bold text-gray-900 dark:text-white leading-tight md:truncate">{{ $burung->nama }}</p>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight md:truncate">{{ $burung->spesies }} · {{ $burung->fase_label }}</p>
    </div>
    @if ($lokasiBadge)
        <span class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-600 dark:text-amber-400">{{ $lokasiBadge }}</span>
    @endif
    @if ($burung->gender_label)
        <span class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $burung->gender === 'jantan' ? 'bg-blue-500/15 text-blue-500' : 'bg-pink-500/15 text-pink-500' }}">{{ $burung->gender_label }}</span>
    @endif
    <button type="button" class="edit-burung shrink-0 w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-blue-500 hover:bg-blue-500/10 transition-colors"
        data-id="{{ $burung->id }}" data-nama="{{ $burung->nama }}" data-spesies="{{ $burung->spesies }}"
        data-fase="{{ $burung->fase }}" data-gender="{{ $burung->gender }}" data-nomor-dna="{{ $burung->nomor_dna }}"
        data-catatan="{{ $burung->catatan }}" aria-label="Edit {{ $burung->nama }}">
        <x-tabler-pencil class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="hapus-burung shrink-0 w-6 h-6 -mr-1 flex items-center justify-center rounded-md text-gray-300 hover:text-[#E62C37] hover:bg-[#E62C37]/10 transition-colors"
        data-id="{{ $burung->id }}" data-nama="{{ $burung->nama }}" aria-label="Hapus {{ $burung->nama }}">
        <x-tabler-x class="w-3.5 h-3.5" />
    </button>
</div>
