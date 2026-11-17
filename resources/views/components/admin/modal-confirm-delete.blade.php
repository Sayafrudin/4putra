@props(['id' => 'delete', 'formId' => 'form-delete-action', 'title' => 'Konfirmasi Penghapusan'])

<x-admin.modal :id="$id" maxWidth="max-w-md" zIndex="z-[60]">
    <x-admin.modal-close :id="$id" />
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
            <x-tabler-alert-triangle class="w-5 h-5 text-red-400" />
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
        </div>
    </div>
    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">{{ $slot }}</p>
    <form id="{{ $formId }}" method="POST">
        @csrf
        @method('DELETE')
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeModal('{{ $id }}')"
                class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-[#151a22] border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">Batal</button>
            <button type="submit"
                class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">Ya, Hapus</button>
        </div>
    </form>
</x-admin.modal>
