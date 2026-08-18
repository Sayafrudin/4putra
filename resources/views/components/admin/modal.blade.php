@props(['id', 'maxWidth' => 'max-w-2xl', 'zIndex' => 'z-50'])

<div id="modal-{{ $id }}"
    class="fixed inset-0 {{ $zIndex }} hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div id="box-{{ $id }}"
        class="w-full {{ $maxWidth }} bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl p-6 overflow-y-auto max-h-[90vh] relative">
        {{ $slot }}
    </div>
</div>

<script>
    // Pastikan modal helper tersedia global
    if (typeof window.showModalBox !== 'function') {
        window.showModalBox = function(id) {
            const m = document.getElementById('modal-' + id);
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        };
        window.closeModalBox = function(id) {
            const m = document.getElementById('modal-' + id);
            if (m) { m.classList.remove('flex'); m.classList.add('hidden'); }
        };
    }
</script>
