@props(['id', 'color' => 'hover:text-white'])

<button type="button" onclick="closeModal('{{ $id }}')"
    class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-gray-400 {{ $color }} transition-colors focus:outline-none z-50">
    <x-tabler-x class="w-5 h-5" />
</button>
