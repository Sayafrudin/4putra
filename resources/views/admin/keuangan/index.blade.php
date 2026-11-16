@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Keuangan Farm</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pemasukan, pengeluaran, gaji bulanan, dan budget pembangunan</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="eksporExcel(this, { namaFile: 'laporan_keuangan_{{ now()->toDateString() }}.xlsx', judul: 'Laporan Keuangan', headers: ['Tanggal', 'Tipe', 'Kategori', 'Jumlah (Rp)', 'Keterangan'], rows: DATA_EKSPOR })"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                Export Excel
            </button>
            <a href="{{ route('admin.keuangan.export-pdf', array_filter(['tipe' => $tipeAktif, 'kategori' => $kategoriAktif])) }}"
                class="px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                Export PDF
            </a>
            <button onclick="bukaModalTambah('masuk')"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                + Uang Masuk
            </button>
            <button onclick="bukaModalTambah('keluar')"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                + Uang Keluar
            </button>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Masuk</p>
            <p class="text-xl font-bold text-green-600 tabular-nums mt-1">{{ number_format((float) $ringkasan['masuk'], 0, ',', '.') }}</p>
        </div>
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Keluar</p>
            <p class="text-xl font-bold text-[#E62C37] tabular-nums mt-1">{{ number_format((float) $ringkasan['keluar'], 0, ',', '.') }}</p>
        </div>
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</p>
            <p class="text-xl font-bold tabular-nums mt-1 {{ $ringkasan['saldo'] >= 0 ? 'text-gray-900 dark:text-white' : 'text-[#E62C37]' }}">{{ number_format((float) $ringkasan['saldo'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Tipe</label>
            <select name="tipe" class="bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white">
                <option value="">Semua</option>
                <option value="masuk" {{ $tipeAktif === 'masuk' ? 'selected' : '' }}>Masuk</option>
                <option value="keluar" {{ $tipeAktif === 'keluar' ? 'selected' : '' }}>Keluar</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Kategori</label>
            <select name="kategori" class="bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white">
                <option value="">Semua</option>
                @foreach ($kategoriList as $k)
                    <option value="{{ $k }}" {{ $kategoriAktif === $k ? 'selected' : '' }}>{{ $k }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
            class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 transition-colors">Terapkan</button>
        @if ($tipeAktif || $kategoriAktif)
            <a href="{{ route('admin.keuangan.index') }}"
                class="px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">Reset</a>
        @endif
    </form>

    {{-- Tabel transaksi --}}
    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4 w-28">Tipe</th>
                        <th class="px-6 py-4 w-32">Kategori</th>
                        <th class="px-6 py-4 w-40">Jumlah</th>
                        <th class="px-6 py-4 w-32">Tanggal</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($transaksis as $t)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-bold {{ $t->tipe === 'masuk' ? 'bg-green-500/10 text-green-600' : 'bg-[#E62C37]/10 text-[#E62C37]' }}">
                                    {{ ucfirst($t->tipe) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $t->kategori }}</td>
                            <td class="px-6 py-4 text-sm font-bold tabular-nums {{ $t->tipe === 'masuk' ? 'text-green-600' : 'text-[#E62C37]' }}">
                                {{ $t->tipe === 'keluar' ? '-' : '' }}{{ $t->jumlah_format }}
                            </td>
                            <td class="px-6 py-4 text-sm tabular-nums">{{ $t->tanggal->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $t->keterangan }}</td>
                            <td class="px-6 py-4 text-right">
                                <button onclick='bukaModalHapus({{ $t->id }}, "{{ addslashes($t->jumlah_format) }}", "{{ addslashes($t->kategori) }}")'
                                    class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada transaksi keuangan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $transaksis->links() }}
        </div>
    </div>

    {{-- ==================== MODAL TAMBAH ==================== --}}
    <div id="modalTambah" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Catat Uang <span id="tambah_tipe_label" class="text-[#E62C37]"></span></h3>
                <button onclick="tutupModal('modalTambah')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formTambah" onsubmit="submitTambah(event)" class="space-y-4" novalidate>
                @csrf
                <input type="hidden" name="tipe" id="tambah_tipe" value="masuk">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Kategori</label>
                    <select name="kategori" id="tambah_kategori"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        @foreach ($kategoriList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" id="tambah_jumlah" min="1" step="1000" placeholder="0"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm tabular-nums text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_jumlah"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tanggal</label>
                    <input type="date" name="tanggal" id="tambah_tanggal" max="{{ now()->toDateString() }}"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_tanggal"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Keterangan (opsional)</label>
                    <textarea name="keterangan" id="tambah_ket" rows="2" placeholder="Misal: gaji pekerja November / setoran dana pembangunan farm"
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Transaksi</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hapus <span id="hapus_info" class="font-semibold text-gray-700 dark:text-gray-200"></span> secara permanen?</p>
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
        // Data ekspor Excel: SELURUH baris sesuai filter aktif (bukan hanya halaman paginasi)
        $dataEkspor = $semuaTransaksi->map(fn ($trx) => [
            $trx->tanggal->translatedFormat('d M Y'),
            ucfirst($trx->tipe),
            $trx->kategori,
            (float) $trx->jumlah,
            $trx->keterangan ?? '',
        ])->concat([
            ['', '', 'TOTAL MASUK', (float) $ringkasan['masuk'], ''],
            ['', '', 'TOTAL KELUAR', (float) $ringkasan['keluar'], ''],
            ['', '', 'SALDO', (float) $ringkasan['saldo'], ''],
        ])->values()->all();
    @endphp
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const DATA_EKSPOR = @json($dataEkspor);
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

        function bukaModalTambah(tipe) {
            bersihkanError(document.getElementById('formTambah'));
            document.getElementById('tambah_tipe').value = tipe;
            document.getElementById('tambah_tipe_label').textContent = tipe === 'masuk' ? 'Masuk' : 'Keluar';
            bukaModal('modalTambah');
        }

        async function submitTambah(e) {
            e.preventDefault();
            const form = document.getElementById('formTambah');
            bersihkanError(form);
            let valid = true;
            if (!form.jumlah.value || Number(form.jumlah.value) < 1) { tandaiError('tambah_jumlah', 'err_tambah_jumlah', 'Jumlah wajib diisi (minimal 1).'); valid = false; }
            if (!form.tanggal.value) { tandaiError('tambah_tanggal', 'err_tambah_tanggal', 'Tanggal wajib diisi.'); valid = false; }
            if (!valid) return;

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        tipe: form.tipe.value,
                        kategori: form.kategori.value,
                        jumlah: Number(form.jumlah.value),
                        tanggal: form.tanggal.value,
                        keterangan: form.keterangan.value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal mencatat transaksi.');
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

        function bukaModalHapus(id, jumlah, kategori) {
            hapusTargetId = id;
            document.getElementById('hapus_info').textContent = kategori + ' ' + jumlah;
            bukaModal('modalHapus');
        }

        document.getElementById('btnHapus').addEventListener('click', async function () {
            if (!hapusTargetId || sedangBusy) return;
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.destroy', ':id') }}'.replace(':id', hapusTargetId), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus transaksi.');
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
