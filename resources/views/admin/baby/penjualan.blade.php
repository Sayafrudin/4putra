@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Penjualan Burung</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Catat penjualan burung — otomatis masuk Kas Umum</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="bukaModalJual()"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                + Catat Penjualan
            </button>
        </div>
    </div>

    {{-- Riwayat penjualan: filter periode (server-side) --}}
    <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Riwayat Penjualan</h3>
        <div class="flex flex-wrap gap-2 items-center">
            <a href="{{ route('admin.penjualan-baby.index') }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ ! $filter ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">Semua</a>
            <a href="{{ route('admin.penjualan-baby.index', ['filter' => 'minggu']) }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ $filter === 'minggu' ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">Minggu Ini</a>
            <a href="{{ route('admin.penjualan-baby.index', ['filter' => '30']) }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ $filter === '30' ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">30 Hari</a>
            <a href="{{ route('admin.penjualan-baby.index', ['filter' => 'bulan']) }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ $filter === 'bulan' ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">Bulan Ini</a>
            <a href="{{ route('admin.penjualan-baby.index', ['filter' => 'tahun']) }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ $filter === 'tahun' ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">Tahun Ini</a>
            <button onclick="bukaModal('modalRentang')"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ $filter === 'rentang' ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">Rentang</button>
        </div>
    </div>
    <div class="mb-4">
        <div class="relative max-w-md">
            <x-tabler-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
            <input type="text" class="table-search-input w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari nama / ring / induk...">
        </div>
    </div>
    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4 w-32">Tanggal</th>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Ring</th>
                        <th class="px-6 py-4">Induk</th>
                        <th class="px-6 py-4 w-28">Umur</th>
                        <th class="px-6 py-4 w-32 text-right">Harga</th>
                        <th class="px-6 py-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($penjualans as $p)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm tabular-nums whitespace-nowrap">{{ $p->tanggal_terjual->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $p->nama_burung }}</td>
                            <td class="px-6 py-4 text-sm">{{ $p->nomor_ring ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $p->induk_label ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums">{{ \App\Support\Umur::label($p->usia_hari) ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-green-600 tabular-nums text-right">Rp {{ number_format((float) $p->harga, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $p->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada penjualan tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL JUAL ==================== --}}
    <div id="modalJual" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Catat Penjualan</h3>
                <button onclick="tutupModal('modalJual')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form id="formJual" onsubmit="submitJual(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Burung yang Dijual</label>
                    <x-admin.select-search name="burung_id" id="jual_burung" placeholder="- Pilih burung -"
                        :options="$burungSemua->map(fn ($b) => [
                            'value' => (string) $b->id,
                            'grup' => $b->kandang ? 'Blok '.$b->kandang->blok : 'Belum Ditempatkan',
                            'label' => $b->nama.' ('.$b->fase_label.($b->nomor_ring ? ' · '.$b->label_ring : '').($b->kandang ? ' · di '.$b->kandang->label : '').')',
                            'ring' => $b->nomor_ring,
                            'induk' => $b->induk_label,
                            'usia' => $b->umur_label,
                            'lokasi' => $b->kandang?->label,
                        ])->all()"
                    />
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_jual_burung"></p>
                </div>
                <div id="jual_info" class="hidden px-4 py-3 text-sm rounded-xl bg-gray-50 dark:bg-[#151a22] border border-gray-200 dark:border-gray-700 space-y-1 text-gray-600 dark:text-gray-300">
                    <p>Ring: <span id="jual_ring" class="font-semibold text-gray-900 dark:text-white">-</span></p>
                    <p>Induk: <span id="jual_induk" class="font-semibold text-gray-900 dark:text-white">-</span></p>
                    <p>Umur: <span id="jual_usia" class="font-semibold text-gray-900 dark:text-white">-</span></p>
                    <p id="jual_lokasi" class="text-red-400 font-semibold hidden">⚠ Masih di kandang — keluarkan dulu sebelum dijual.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tanggal Terjual</label>
                        <input type="date" name="tanggal_terjual" id="jual_tanggal" data-isi-hari-ini max="{{ now('Asia/Jakarta')->toDateString() }}"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_jual_tanggal"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Harga (Rp)</label>
                        <input type="number" name="harga" id="jual_harga" min="0" step="1000"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_jual_harga"></p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Keterangan</label>
                    <textarea name="keterangan" id="jual_keterangan" rows="2"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalJual')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnJual"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                        Jual &amp; Masukkan Kas Umum
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL RENTANG ==================== --}}
    <div id="modalRentang" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Rentang Tanggal</h3>
                <button onclick="tutupModal('modalRentang')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Dari</label>
                    <input type="date" id="rentang_dari" value="{{ request('dari') }}"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Sampai</label>
                    <input type="date" id="rentang_sampai" value="{{ request('sampai') }}"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalRentang')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="button" onclick="terapkanRentang()"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                        Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var CSRF = document.querySelector('meta[name="csrf-token"]').content;
        var sedangBusy = false;

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnJual').forEach(btn => {
                btn.disabled = on;
                btn.classList.toggle('opacity-50', on);
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
            const input = document.getElementById(inputId);
            if (input) input.classList.add('border-red-400');
            const err = document.getElementById(errId);
            if (err) { err.textContent = pesan; err.classList.remove('hidden'); }
        }

        // ==================== JUAL ====================
        function bukaModalJual() {
            const form = document.getElementById('formJual');
            bersihkanError(form);
            form.reset();
            document.getElementById('jual_info').classList.add('hidden');
            document.getElementById('jual_lokasi').classList.add('hidden');
            window.__ss?.burung_id?.kosongkan();
            bukaModal('modalJual');
        }

        // Pilihan burung (dropdown ber-search) → panel ring/induk/umur terisi otomatis.
        // Hook sinkron dari komponen select-search (bukan event — lihat komponen).
        window.__ssHook = window.__ssHook || {};
        window.__ssHook.burung_id = (op) => {
            const info = document.getElementById('jual_info');
            if (!op || op.value === '') { info.classList.add('hidden'); return; }
            info.classList.remove('hidden');
            document.getElementById('jual_ring').textContent = op.ring || '-';
            document.getElementById('jual_induk').textContent = op.induk || '-';
            document.getElementById('jual_usia').textContent = op.usia || '-';
            document.getElementById('jual_lokasi').classList.toggle('hidden', !op.lokasi);
        };

        async function submitJual(e) {
            e.preventDefault();
            const form = document.getElementById('formJual');
            bersihkanError(form);
            let valid = true;
            if (!form.burung_id.value) { tandaiError('jual_burung', 'err_jual_burung', 'Pilih burung yang dijual.'); valid = false; }
            if (!form.tanggal_terjual.value) { tandaiError('jual_tanggal', 'err_jual_tanggal', 'Tanggal terjual wajib diisi.'); valid = false; }
            if (!form.harga.value || Number(form.harga.value) < 0) { tandaiError('jual_harga', 'err_jual_harga', 'Harga wajib diisi (≥ 0).'); valid = false; }
            if (!valid) return;

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.baby.jual') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        burung_id: Number(form.burung_id.value),
                        tanggal_terjual: form.tanggal_terjual.value,
                        harga: Number(form.harga.value),
                        keterangan: form.keterangan.value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) { showToast('error', 'Gagal', data.message || 'Gagal mencatat penjualan.'); return; }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalJual');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        // ==================== FILTER RENTANG ====================
        function terapkanRentang() {
            const dari = document.getElementById('rentang_dari').value;
            const sampai = document.getElementById('rentang_sampai').value;
            if (!dari && !sampai) { showToast('error', 'Gagal', 'Isi minimal satu tanggal.'); return; }
            const url = new URL('{{ route('admin.penjualan-baby.index') }}');
            url.searchParams.set('filter', 'rentang');
            if (dari) url.searchParams.set('dari', dari);
            if (sampai) url.searchParams.set('sampai', sampai);
            window.location.href = url.toString();
        }
    </script>
@endsection
