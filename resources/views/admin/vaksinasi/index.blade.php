@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Vaksinasi Burung</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Catat vaksin dan pantau booster bulanan tiap burung</p>
        </div>
        <div class="flex gap-3">
            <button onclick="bukaModalTambah()"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                + Catat Vaksin
            </button>
        </div>
    </div>

    {{-- Daftar jatuh tempo: vaksin terakhir tiap burung sudah lewat 1 bulan --}}
    <div class="mb-8 bg-white dark:bg-[#1e2530] border {{ $jatuhTempo->isNotEmpty() ? 'border-[#E62C37]/40' : 'border-gray-200 dark:border-gray-800' }} rounded-xl shadow-sm">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Perlu Vaksin Ulang
                (booster bulanan)</h3>
            <span class="text-xs font-bold {{ $jatuhTempo->isNotEmpty() ? 'text-[#E62C37] bg-[#E62C37]/10' : 'text-green-600 bg-green-500/10' }} px-2.5 py-1 rounded-full">
                {{ $jatuhTempo->isNotEmpty() ? $jatuhTempo->count().' burung' : 'Aman' }}
            </span>
        </div>
        <div class="p-4">
            @forelse ($jatuhTempo as $v)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $v->burung->nama }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $v->burung->spesies }} · vaksin terakhir "{{ $v->jenis }}" pada {{ $v->tanggal_vaksin->translatedFormat('d M Y') }}</p>
                    </div>
                    <span class="text-xs font-bold text-[#E62C37] bg-[#E62C37]/10 px-2.5 py-1 rounded-lg self-start">Telah {{ (int) $v->tanggal_vaksin->diffInMonths(now()) }} bulan</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 py-2">Semua burung sudah tervaksin dalam 1 bulan terakhir. 👍</p>
            @endforelse
        </div>
    </div>

    {{-- Riwayat vaksinasi --}}
    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4">Burung</th>
                        <th class="px-6 py-4">Jenis Vaksin</th>
                        <th class="px-6 py-4 w-36">Tanggal</th>
                        <th class="px-6 py-4 w-32">Status</th>
                        <th class="px-6 py-4 w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($riwayat as $v)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $v->burung->nama }}</td>
                            <td class="px-6 py-4 text-sm">{{ $v->jenis }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums">{{ $v->tanggal_vaksin->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $v->sudah_jatuh_tempo ? 'bg-[#E62C37]/10 text-[#E62C37]' : 'bg-green-500/10 text-green-600' }}">
                                    {{ $v->sudah_jatuh_tempo ? 'Perlu vaksin ulang' : 'Masih terlindungi' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick='bukaModalHapus({{ $v->id }}, "{{ addslashes($v->jenis) }}", "{{ addslashes($v->burung->nama) }}")'
                                    class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada catatan vaksinasi. Klik "+ Catat Vaksin" untuk memulai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL CATAT VAKSIN ==================== --}}
    <div id="modalTambah" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Catat Vaksinasi</h3>
                <button onclick="tutupModal('modalTambah')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formTambah" onsubmit="submitTambah(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Burung</label>
                    <select name="burung_id" id="tambah_burung"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <option value="">— Pilih burung —</option>
                        @foreach ($burungList as $b)
                            <option value="{{ $b->id }}">{{ $b->nama }} — {{ $b->spesies }} ({{ $b->fase === 'anakan' ? 'Baby' : 'Dewasa' }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_burung"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jenis Vaksin</label>
                    <input type="text" name="jenis" id="tambah_jenis" placeholder="Contoh: NDV (Newcastle) / Avian Influenza"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_jenis"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tanggal Vaksin</label>
                    <input type="date" name="tanggal_vaksin" id="tambah_tanggal" max="{{ now()->toDateString() }}"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_tanggal"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Catatan (opsional)</label>
                    <textarea name="catatan" id="tambah_catatan" rows="2" placeholder="Dosis, dokter hewan, reaksi..."
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalTambah')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnTambah"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL HAPUS ==================== --}}
    <div id="modalHapus" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-[#E62C37]/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#E62C37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Catatan Vaksinasi</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hapus "<span id="hapus_jenis" class="font-semibold text-gray-700 dark:text-gray-200"></span>" milik <span id="hapus_burung" class="font-semibold text-gray-700 dark:text-gray-200"></span>?</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="tutupModal('modalHapus')"
                    class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                    Batal
                </button>
                <button type="button" id="btnHapus"
                    class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        let sedangBusy = false;
        let hapusTargetId = null;

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnTambah, #btnHapus').forEach(btn => {
                btn.disabled = on;
                btn.classList.toggle('opacity-60', on);
                btn.classList.toggle('cursor-not-allowed', on);
            });
        }

        function tutupModal(id) {
            const m = document.getElementById(id);
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        function bukaModal(id) {
            const m = document.getElementById(id);
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function bukaModalTambah() {
            bersihkanError(document.getElementById('formTambah'));
            bukaModal('modalTambah');
        }

        function tandaiError(inputId, errId, pesan) {
            document.getElementById(inputId).classList.add('border-red-400');
            const err = document.getElementById(errId);
            err.textContent = pesan;
            err.classList.remove('hidden');
        }

        function bersihkanError(form) {
            form.querySelectorAll('input, select, textarea').forEach(el => el.classList.remove('border-red-400'));
            form.querySelectorAll('.text-red-400').forEach(p => p.classList.add('hidden'));
        }

        async function submitTambah(e) {
            e.preventDefault();
            const form = document.getElementById('formTambah');
            bersihkanError(form);

            let valid = true;
            if (!form.burung_id.value) { tandaiError('tambah_burung', 'err_tambah_burung', 'Pilih burung terlebih dahulu.'); valid = false; }
            if (!form.jenis.value.trim()) { tandaiError('tambah_jenis', 'err_tambah_jenis', 'Jenis vaksin wajib diisi.'); valid = false; }
            if (!form.tanggal_vaksin.value) { tandaiError('tambah_tanggal', 'err_tambah_tanggal', 'Tanggal vaksin wajib diisi.'); valid = false; }
            if (!valid) return;

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.vaksinasi.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        burung_id: Number(form.burung_id.value),
                        jenis: form.jenis.value.trim(),
                        tanggal_vaksin: form.tanggal_vaksin.value,
                        catatan: form.catatan.value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal mencatat vaksinasi.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalTambah');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        function bukaModalHapus(id, jenis, burung) {
            hapusTargetId = id;
            document.getElementById('hapus_jenis').textContent = jenis;
            document.getElementById('hapus_burung').textContent = burung;
            bukaModal('modalHapus');
        }

        document.getElementById('btnHapus').addEventListener('click', async function () {
            if (!hapusTargetId || sedangBusy) return;
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.vaksinasi.destroy', ':id') }}'.replace(':id', hapusTargetId), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus catatan.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalHapus');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        });
    </script>
@endsection
