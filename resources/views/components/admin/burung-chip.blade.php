@props(['burung'])

<div class="burung-chip group relative flex items-center gap-2 bg-gray-50 dark:bg-[#1e2530] border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 cursor-grab active:cursor-grabbing select-none"
    draggable="true"
    data-burung-id="{{ $burung->id }}"
    data-kandang-id="{{ $burung->kandang_id }}"
    title="{{ $burung->nama }} — {{ $burung->spesies }} ({{ $burung->fase_label }}){{ $burung->gender_label ? ' · '.$burung->gender_label : '' }}">
    <span class="w-2 h-2 rounded-full bg-[#E62C37] shrink-0"></span>
    <span class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $burung->nama }}</span>
    <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 truncate">{{ $burung->fase_label }}{{ $burung->gender_label ? ' · '.$burung->gender_label : '' }}</span>
    <button type="button" class="hapus-burung ml-auto shrink-0 w-4 h-4 flex items-center justify-center rounded text-gray-300 hover:text-[#E62C37] hover:bg-[#E62C37]/10 transition-colors"
        data-id="{{ $burung->id }}" data-nama="{{ $burung->nama }}" aria-label="Hapus {{ $burung->nama }}">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>
