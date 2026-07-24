@props(['id', 'color' => 'hover:text-white'])

<button type="button" onclick="closeModal('{{ $id }}')"
    class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-gray-400 {{ $color }} transition-colors focus:outline-none z-50">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>
