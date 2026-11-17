@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Stok Makanan Burung</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Biji-bijian & pellet - estimasi habis dan prediksi kapan beli lagi dihitung otomatis</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="eksporExcel(this, { namaFile: 'laporan_stok_makanan_{{ now()->toDateString() }}.xlsx', judul: 'Laporan Stok Makanan', headers: ['Nama Pakan', 'Stok', 'Konsumsi/Hari', 'Estimasi Habis (hari)', 'Perkiraan Beli Lagi', 'Catatan'], rows: DATA_EKSPOR })"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                Export Excel
            </button>
            <a href="{{ route('admin.stok.export-pdf') }}"
                class="px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-[#262d3a] rounded-xl transition-colors">
                Export PDF
            </a>
            <button onclick="bukaModalTambah()"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                + Tambah Stok
            </button>
        </div>
    </div>

    {{-- Input search --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" class="table-search-input w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari nama pakan...">
        </div>
    </div>

    @if ($perluBeli->isNotEmpty())
        <div class="mb-8 p-4 bg-white dark:bg-[#1e2530] border border-[#E62C37]/40 rounded-xl shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-[#E62C37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <h3 class="text-sm font-bold uppercase tracking-wider text-[#E62C37]">Segera Beli (stok < 2 minggu)</h3>
            </div>
            <ul class="flex flex-wrap gap-2">
                @foreach ($perluBeli as $s)
                    <li class="text-xs font-semibold bg-[#E62C37]/10 text-[#E62C37] px-2.5 py-1.5 rounded-lg">
                        {{ $s->nama }} - tinggal {{ $s->estimasi_hari }} hari
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4">Nama Pakan</th>
                        <th class="px-6 py-4 w-32">Stok</th>
                        <th class="px-6 py-4 w-36">Konsumsi/Hari</th>
                        <th class="px-6 py-4 w-40">Estimasi Habis</th>
                        <th class="px-6 py-4 w-44">Perkiraan Beli Lagi</th>
                        <th class="px-6 py-4 w-32 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($stoks as $s)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $s->nama }}
                                @if ($s->perlu_beli)
                                    <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#E62C37]/10 text-[#E62C37]">Segera beli</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-bold tabular-nums">{{ number_format((float) $s->jumlah_stok, 2, ',', '.') }} {{ $s->satuan }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums">{{ number_format((float) $s->konsumsi_per_hari, 2, ',', '.') }} {{ $s->satuan }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums">
                                {{ $s->estimasi_hari }} hari
                                <span class="block text-xs text-gray-500 dark:text-gray-400">(±{{ max(1, (int) ceil($s->estimasi_hari / 30)) }} bulan)</span>
                            </td>
                            <td class="px-6 py-4 text-sm tabular-nums {{ $s->perlu_beli ? 'font-bold text-[#E62C37]' : '' }}">
                                {{ $s->tanggal_beli_ulang?->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick='bukaModalUpdate({{ json_encode([
                                        "id" => $s->id,
                                        "nama" => $s->nama,
                                        "satuan" => $s->satuan,
                                        "jumlah_stok" => (float) $s->jumlah_stok,
                                        "konsumsi_per_hari" => (float) $s->konsumsi_per_hari,
                                    ]) }})'
                                        class="px-3 py-1.5 text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors">
                                        Update
                                    </button>
                                    <button onclick='bukaModalHapus({{ $s->id }}, "{{ addslashes($s->nama) }}")'
                                        class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada stok makanan tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL TAMBAH ==================== --}}
    <div id="modalTambah" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tambah Stok Makanan</h3>
                <button onclick="tutupModal('modalTambah')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formTambah" onsubmit="submitForm(event, '{{ route('admin.stok.store') }}', 'POST')" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Pakan</label>
                    <input type="text" name="nama" id="tambah_nama" placeholder="Contoh: Sunseed Mix / Kaytee Pellet"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_nama"></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jumlah Stok</label>
                        <div class="relative">
                            <input type="number" name="jumlah_stok" id="tambah_stok" min="0" step="0.01" placeholder="0"
                                class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 pr-14 text-sm tabular-nums text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <span class="satuan-badge-tambah pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-bold text-gray-500 dark:text-gray-400">kg</span>
                        </div>
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_stok"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Satuan</label>
                        <select name="satuan" id="tambah_satuan" onchange="sinkronSatuan('tambah')"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="kg">kg</option>
                            <option value="gr">gr</option>
                            <option value="ons">ons</option>
                            <option value="sak">sak</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Konsumsi / Hari</label>
                        <div class="relative">
                            <input type="number" name="konsumsi_per_hari" id="tambah_konsumsi" min="0.01" step="0.01" placeholder="0.5"
                                class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 pr-14 text-sm tabular-nums text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <span class="satuan-badge-tambah pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-bold text-gray-500 dark:text-gray-400">kg</span>
                        </div>
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_konsumsi"></p>
                    </div>
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

    {{-- ==================== MODAL UPDATE (setelah beli / ubah konsumsi) ==================== --}}
    <div id="modalUpdate" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Update <span id="update_nama_label" class="text-[#E62C37]"></span></h3>
                <button onclick="tutupModal('modalUpdate')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formUpdate" onsubmit="submitForm(event, formActionUpdate, 'PUT')" class="space-y-4" novalidate>
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Stok Sekarang</label>
                        <div class="relative">
                            <input type="number" name="jumlah_stok" id="update_stok" min="0" step="0.01"
                                class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 pr-14 text-sm tabular-nums text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <span class="satuan-badge-update pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-bold text-gray-500 dark:text-gray-400">kg</span>
                        </div>
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_update_stok"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Satuan</label>
                        <select name="satuan" id="update_satuan" onchange="sinkronSatuan('update')"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="kg">kg</option>
                            <option value="gr">gr</option>
                            <option value="ons">ons</option>
                            <option value="sak">sak</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Konsumsi / Hari</label>
                        <div class="relative">
                            <input type="number" name="konsumsi_per_hari" id="update_konsumsi" min="0.01" step="0.01"
                                class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 pr-14 text-sm tabular-nums text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <span class="satuan-badge-update pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-bold text-gray-500 dark:text-gray-400">kg</span>
                        </div>
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_update_konsumsi"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalUpdate')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnUpdate"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                        Perbarui
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Stok</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hapus <span id="hapus_nama" class="font-semibold text-gray-700 dark:text-gray-200"></span>?</p>
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

    @php
        // Data ekspor Excel (seluruh stok, server-side)
        $dataEkspor = $stoks->map(fn ($s) => [
            $s->nama,
            number_format((float) $s->jumlah_stok, 2, ',', '.').' '.$s->satuan,
            number_format((float) $s->konsumsi_per_hari, 2, ',', '.').' '.$s->satuan,
            $s->estimasi_hari !== null ? $s->estimasi_hari.' hari' : '-',
            $s->tanggal_beli_ulang?->translatedFormat('d M Y') ?? '-',
            $s->catatan ?: '-',
        ])->values()->all();
    @endphp

    <script>
        var CSRF = document.querySelector('meta[name="csrf-token"]').content;
        var sedangBusy = false;
        var updateTargetId = null;
        var hapusTargetId = null;
        var formActionUpdate = '';
        var DATA_EKSPOR = @json($dataEkspor);

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnTambah, #btnUpdate, #btnHapus').forEach(btn => {
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

        function bersihkanError(form) {
            form.querySelectorAll('input, select, textarea').forEach(el => el.classList.remove('border-red-400'));
            form.querySelectorAll('.text-red-400').forEach(p => p.classList.add('hidden'));
        }

        function tandaiError(inputId, errId, pesan) {
            document.getElementById(inputId).classList.add('border-red-400');
            const err = document.getElementById(errId);
            err.textContent = pesan;
            err.classList.remove('hidden');
        }

        function bukaModalTambah() {
            bersihkanError(document.getElementById('formTambah'));
            sinkronSatuan('tambah');
            bukaModal('modalTambah');
        }

        // Badge satuan di input stok & konsumsi ikut nilai dropdown
        function sinkronSatuan(scope) {
            const val = document.getElementById(scope + '_satuan').value;
            document.querySelectorAll('.satuan-badge-' + scope).forEach(el => el.textContent = val);
        }

        function bukaModalUpdate(data) {
            updateTargetId = data.id;
            formActionUpdate = '{{ route('admin.stok.update', ':id') }}'.replace(':id', data.id);
            document.getElementById('update_nama_label').textContent = data.nama;
            document.getElementById('update_stok').value = data.jumlah_stok;
            const sel = document.getElementById('update_satuan');
            // Satuan lama di luar daftar (data sebelum ada dropdown) tetap bisa tampil & tersimpan
            if (![...sel.options].some(o => o.value === data.satuan)) sel.add(new Option(data.satuan, data.satuan));
            sel.value = data.satuan;
            document.getElementById('update_konsumsi').value = data.konsumsi_per_hari;
            sinkronSatuan('update');
            bersihkanError(document.getElementById('formUpdate'));
            bukaModal('modalUpdate');
        }

        async function submitForm(e, url, method) {
            e.preventDefault();
            const form = e.target;
            bersihkanError(form);

            const isTambah = method === 'POST';
            if (isTambah) {
                let valid = true;
                if (!form.nama.value.trim()) { tandaiError('tambah_nama', 'err_tambah_nama', 'Nama pakan wajib diisi.'); valid = false; }
                if (form.jumlah_stok.value === '' || Number(form.jumlah_stok.value) < 0) { tandaiError('tambah_stok', 'err_tambah_stok', 'Jumlah stok wajib diisi.'); valid = false; }
                if (!form.konsumsi_per_hari.value || Number(form.konsumsi_per_hari.value) <= 0) { tandaiError('tambah_konsumsi', 'err_tambah_konsumsi', 'Konsumsi per hari wajib diisi (lebih dari 0) agar prediksi bisa dihitung.'); valid = false; }
                if (!valid) return;
            } else {
                let valid = true;
                if (form.jumlah_stok.value === '' || Number(form.jumlah_stok.value) < 0) { tandaiError('update_stok', 'err_update_stok', 'Jumlah stok wajib diisi.'); valid = false; }
                if (!form.konsumsi_per_hari.value || Number(form.konsumsi_per_hari.value) <= 0) { tandaiError('update_konsumsi', 'err_update_konsumsi', 'Konsumsi per hari wajib diisi (lebih dari 0).'); valid = false; }
                if (!valid) return;
            }

            const body = isTambah
                ? { nama: form.nama.value.trim(), jumlah_stok: Number(form.jumlah_stok.value), satuan: form.satuan.value.trim() || 'kg', konsumsi_per_hari: Number(form.konsumsi_per_hari.value) }
                : { jumlah_stok: Number(form.jumlah_stok.value), satuan: form.satuan.value.trim() || 'kg', konsumsi_per_hari: Number(form.konsumsi_per_hari.value) };

            setBusy(true);
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menyimpan stok.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal(isTambah ? 'modalTambah' : 'modalUpdate');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        function bukaModalHapus(id, nama) {
            hapusTargetId = id;
            document.getElementById('hapus_nama').textContent = nama;
            bukaModal('modalHapus');
        }

        document.getElementById('btnHapus').addEventListener('click', async function () {
            if (!hapusTargetId || sedangBusy) return;
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.stok.destroy', ':id') }}'.replace(':id', hapusTargetId), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus stok.');
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
