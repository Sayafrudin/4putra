@props(['burung', 'lokasiBadge' => null])

{{-- Chip responsif: di HP kartu lebar penuh, nama & gender terbaca utuh.
     Ring & catatan tampil di bawah nama (juga di mobile). Burung ber-catatan
     diberi border amber — penanda kondisi khusus langsung terlihat. --}}
<div class="burung-chip group relative flex items-center gap-2 bg-gray-50 dark:bg-[#1e2530] border {{ $burung->catatan ? 'border-amber-400/70 dark:border-amber-400/50' : 'border-gray-200 dark:border-gray-700' }} rounded-lg px-2.5 py-2 cursor-grab active:cursor-grabbing select-none w-full sm:w-auto"
    draggable="true"
    data-burung-id="{{ $burung->id }}"
    data-nama="{{ $burung->nama }}"
    data-kandang-id="{{ $burung->kandang_id }}"
    title="{{ $burung->nama }} - {{ $burung->spesies }} ({{ $burung->fase_label }}){{ $burung->gender_label ? ' · '.$burung->gender_label : '' }}{{ $burung->nomor_ring ? ' · Ring '.$burung->nomor_ring : '' }}{{ $burung->catatan ? ' · '.$burung->catatan : '' }}">
    <span class="w-2 h-2 rounded-full bg-[#E62C37] shrink-0"></span>
    <div class="min-w-0 grow">
        <p class="text-xs font-bold text-gray-900 dark:text-white leading-tight md:truncate">{{ $burung->nama }}</p>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight md:truncate">{{ $burung->spesies }} · {{ $burung->fase_label }}</p>
        @if ($burung->nomor_ring)
            <p class="text-[10px] font-semibold text-blue-500 leading-tight md:truncate">Ring: {{ $burung->nomor_ring }}</p>
        @endif
        @if ($burung->catatan)
            <p class="text-[10px] font-medium text-amber-600 dark:text-amber-400 leading-tight md:truncate">{{ $burung->catatan }}</p>
        @endif
    </div>
    @if ($lokasiBadge)
        <span class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-600">{{ $lokasiBadge }}</span>
    @endif
    @if ($burung->gender_label)
        <span class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $burung->gender === 'jantan' ? 'bg-blue-500/20 text-blue-600' : 'bg-pink-500/20 text-pink-600' }}">{{ $burung->gender_label }}</span>
    @endif
    <button type="button" class="edit-burung shrink-0 w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-blue-500 hover:bg-blue-500/10 transition-colors"
        data-id="{{ $burung->id }}" data-nama="{{ $burung->nama }}" data-spesies="{{ $burung->spesies }}"
        data-fase="{{ $burung->fase }}" data-gender="{{ $burung->gender }}" data-nomor-dna="{{ $burung->nomor_dna }}"
        data-nomor-ring="{{ $burung->nomor_ring }}" data-catatan="{{ $burung->catatan }}" aria-label="Edit burung">
        <x-tabler-pencil class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="hapus-burung shrink-0 w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-red-500 hover:bg-red-500/10 transition-colors"
        data-id="{{ $burung->id }}" data-nama="{{ $burung->nama }}" aria-label="Hapus burung">
        <x-tabler-x class="w-3.5 h-3.5" />
    </button>
</div>
