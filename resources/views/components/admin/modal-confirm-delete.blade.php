@props(['id' => 'delete', 'formId' => 'form-delete-action', 'title' => 'Konfirmasi Penghapusan'])

<x-admin.modal :id="$id" maxWidth="max-w-md" zIndex="z-[60]">
    <x-admin.modal-close :id="$id" />
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-white">{{ $title }}</h3>
            <p class="text-sm text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
        </div>
    </div>
    <p class="text-sm text-gray-300 mb-6">{{ $slot }}</p>
    <form id="{{ $formId }}" method="POST">
        @csrf
        @method('DELETE')
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeModal('{{ $id }}')"
                class="px-4 py-2.5 text-sm font-semibold text-gray-300 bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
            <button type="submit"
                class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">Ya, Hapus</button>
        </div>
    </form>
</x-admin.modal>
