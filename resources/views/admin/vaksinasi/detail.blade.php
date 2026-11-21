@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <button type="button" onclick="window.location.href='{{ route('admin.vaksinasi.index') }}'"
                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#E62C37] bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-1.5 transition-colors">← Kembali ke Obat & Vaksinasi</button>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase mt-1">Obat & Vaksinasi — {{ $kandang->label }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Riwayat pengobatan & vaksinasi burung di kandang ini</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="eksporExcel(this, { namaFile: 'laporan_vaksinasi_{{ $kandang->label }}_{{ now()->toDateString() }}.xlsx', judul: 'Laporan Obat & Vaksinasi — {{ $kandang->label }}', headers: ['Burung', 'Jenis', 'Metode', 'Dosis', 'Tanggal', 'Status Ulang'], rows: DATA_EKSPOR })"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                Export Excel
            </button>
            <a href="{{ route('admin.vaksinasi.detail-pdf', $kandang) }}"
                class="px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                Export PDF
            </a>
            <button onclick="bukaModalCatat({{ $burungList->pluck('id')->values()->toJson() }})"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                + Catat Massal
            </button>
        </div>
    </div>

    {{-- Daftar burung kandang ini + status terakhirnya --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-8">
        @forelse ($burungList as $b)
            @php
                $terakhir = $terakhirPerBurung->get($b->id);
                $aman = $terakhir && ! $terakhir->sudah_jatuh_tempo;
            @endphp
            <div class="bg-white dark:bg-[#1e2530] border {{ $aman ? 'border-gray-200 dark:border-gray-800' : 'border-[#E62C37]/40' }} rounded-xl p-3">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-2 h-2 rounded-full shrink-0 {{ $aman ? 'bg-green-600' : 'bg-[#E62C37]' }}"></span>
                    <span role="button" tabindex="0" onclick="bukaDetailBurung({{ $b->id }})"
                        class="text-sm font-bold text-gray-900 dark:text-white truncate cursor-pointer hover:text-[#E62C37] transition-colors"
                        title="Lihat identitas burung">{{ $b->nama }}</span>
                    <span class="ml-auto px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $aman ? 'text-green-600 bg-green-600/10' : 'text-[#E62C37] bg-[#E62C37]/10' }}">{{ $aman ? 'Aman' : 'Perlu' }}</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $b->spesies }}@if ($b->nomor_ring) · {{ $b->label_ring }}@endif</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                    {{ $terakhir ? $terakhir->jenis.' ('.($terakhir->metode === 'oral' ? 'Oral' : 'Suntik').') · '.$terakhir->tanggal_vaksin->translatedFormat('d M Y') : 'Belum ada catatan' }}
                </p>
                <button onclick='bukaModalCatat([{{ $b->id }}])'
                    class="mt-2 px-3 py-1.5 text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors">
                    Catat
                </button>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 p-6 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl text-center text-gray-500 dark:text-gray-400 text-sm">
                Kandang ini belum berisi burung.
            </div>
        @endforelse
    </div>

    {{-- Riwayat kandang ini --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <x-tabler-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
            <input type="text" class="table-search-input w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari burung / jenis / metode...">
        </div>
    </div>
    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4">Burung</th>
                        <th class="px-6 py-4">Jenis</th>
                        <th class="px-6 py-4 w-24">Metode</th>
                        <th class="px-6 py-4 w-32">Dosis</th>
                        <th class="px-6 py-4 w-32">Tanggal</th>
                        <th class="px-6 py-4 w-28">Status Ulang</th>
                        <th class="px-6 py-4 w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($riwayat as $v)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $v->burung?->nama ?? '-' }}
                                @if ($v->burung?->nomor_ring)
                                    <span class="text-xs font-normal text-gray-500">· {{ $v->burung->label_ring }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $v->jenis }}</td>
                            <td class="px-6 py-4 text-sm">{{ $v->metode === 'oral' ? 'Oral' : 'Suntik' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $v->dosis ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums whitespace-nowrap">{{ $v->tanggal_vaksin->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $v->sudah_jatuh_tempo ? 'bg-red-500/10 text-red-500' : 'bg-green-500/10 text-green-600' }}">
                                    {{ $v->sudah_jatuh_tempo ? 'Perlu ulang' : 'Terlindungi' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick='bukaModalHapus({{ $v->id }}, "{{ addslashes($v->jenis) }}", "{{ addslashes($v->burung?->nama ?? '-') }}")'
                                    class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada catatan untuk kandang ini. Klik "+ Catat Massal" atau catat per burung di atas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL CATAT ==================== --}}
    <div id="modalCatat" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Catat Obat / Vaksin <span class="text-[#E62C37]">{{ $kandang->label }}</span></h3>
                <button onclick="tutupModal('modalCatat')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form id="formCatat" onsubmit="submitCatat(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jenis</label>
                    <select name="jenis_pilih" id="catat_jenis_pilih" onchange="ubahJenis()"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                        @foreach (\App\Models\Vaksinasi::JENIS_STANDAR as $j)
                            <option value="{{ $j }}">{{ $j }}</option>
                        @endforeach
                        <option value="Lainnya">Lainnya (tulis sendiri)</option>
                    </select>
                    <input type="text" name="jenis" id="catat_jenis" placeholder="Tulis nama obat/vaksin..."
                        class="hidden w-full mt-2 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_catat_jenis"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Metode</label>
                    <div class="flex gap-3">
                        <label class="flex-1 flex items-center gap-2 p-3 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer has-checked:border-[#E62C37]">
                            <input type="radio" name="metode" value="suntik" checked class="accent-[#E62C37]"> <span class="text-sm text-gray-900 dark:text-white">Suntik <span class="text-xs text-gray-500">(1x/tahun)</span></span>
                        </label>
                        <label class="flex-1 flex items-center gap-2 p-3 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer has-checked:border-[#E62C37]">
                            <input type="radio" name="metode" value="oral" class="accent-[#E62C37]"> <span class="text-sm text-gray-900 dark:text-white">Oral <span class="text-xs text-gray-500">(3x/tahun)</span></span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tanggal Pemberian</label>
                    <input type="date" name="tanggal_vaksin" id="catat_tanggal" max="{{ now('Asia/Jakarta')->toDateString() }}"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_catat_tanggal"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Dosis (opsional, per burung)</label>
                    <div id="catat_dosis_rows" class="space-y-2"></div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Catatan</label>
                    <textarea name="catatan" id="catat_catatan" rows="2"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalCatat')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnCatat"
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
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-[#E62C37]/10 flex items-center justify-center shrink-0">
                    <x-tabler-alert-triangle class="w-5 h-5 text-[#E62C37]" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Catatan</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hapus catatan <span id="hapus_jenis" class="font-semibold text-gray-700 dark:text-gray-200"></span> milik <span id="hapus_burung" class="font-semibold text-gray-700 dark:text-gray-200"></span>?</p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
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

    {{-- Modal identitas burung (klik nama burung di card) --}}
    <x-admin.detail-burung :burungs="$burungList" />

    @php
        $dataEkspor = $riwayat->map(fn ($v) => [
            $v->burung?->nama_ring ?? '-',
            $v->jenis,
            $v->metode === 'oral' ? 'Oral' : 'Suntik',
            $v->dosis ?? '',
            $v->tanggal_vaksin->translatedFormat('d M Y'),
            $v->sudah_jatuh_tempo ? 'Perlu ulang' : 'Terlindungi',
        ])->values()->all();
    @endphp
    <script>
        var CSRF = document.querySelector('meta[name="csrf-token"]').content;
        // PENTING: data nama burung dirender dengan direktif json khusus (bukan
        // kurung-kurawal ganda) — escaping HTML di dalam <script> = syntax error,
        // seluruh fungsi modal mati (bug tombol Catat tidak beraksi).
        @php
            $infoBurung = $burungList->mapWithKeys(fn ($b) => [
                $b->id => ['nama' => $b->nama, 'ring' => $b->nomor_ring, 'gender' => $b->gender_label],
            ])->all();
        @endphp
        var BURUNG_INFO = @json($infoBurung, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        var DATA_EKSPOR = @json($dataEkspor, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        var sedangBusy = false;
        var catatTargetIds = [];
        var hapusTargetId = null;

        // Identitas burung utk label: "Nama (Ring X) · Jantan" — bagian opsional
        // hilang bila kosong (konvensi identitas burung).
        function labelBurung(b) {
            let t = b.nama + (b.ring ? ' (Ring ' + b.ring + ')' : '');
            if (b.gender) t += ' · ' + b.gender;
            return t;
        }

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnCatat, #btnHapus').forEach(btn => {
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

        // ===== Modal catat =====
        function ubahJenis() {
            const pilih = document.getElementById('catat_jenis_pilih');
            const input = document.getElementById('catat_jenis');
            input.classList.toggle('hidden', pilih.value !== 'Lainnya');
            if (pilih.value !== 'Lainnya') input.value = '';
        }

        function bukaModalCatat(ids) {
            catatTargetIds = ids;
            const form = document.getElementById('formCatat');
            bersihkanError(form);
            form.reset();
            ubahJenis();
            const wrap = document.getElementById('catat_dosis_rows');
            wrap.innerHTML = '';
            ids.forEach(function (id) {
                const b = BURUNG_INFO[id] || { nama: '#' + id, ring: null, gender: null };
                const baris = document.createElement('div');
                baris.className = 'flex items-center gap-2';
                const label = document.createElement('span');
                label.className = 'text-xs font-semibold text-gray-600 dark:text-gray-300 w-44 truncate';
                label.title = labelBurung(b);
                label.textContent = labelBurung(b);
                const input = document.createElement('input');
                input.type = 'text';
                input.dataset.dosis = id;
                input.placeholder = 'dosis...';
                input.className = 'flex-1 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none';
                baris.append(label, input);
                wrap.appendChild(baris);
            });
            bukaModal('modalCatat');
        }

        async function submitCatat(e) {
            e.preventDefault();
            const form = document.getElementById('formCatat');
            bersihkanError(form);
            const jenis = form.jenis_pilih.value === 'Lainnya' ? form.jenis.value.trim() : form.jenis_pilih.value;
            let valid = true;
            if (!jenis) { tandaiError('catat_jenis', 'err_catat_jenis', 'Jenis wajib diisi.'); valid = false; }
            if (!form.tanggal_vaksin.value) { tandaiError('catat_tanggal', 'err_catat_tanggal', 'Tanggal wajib diisi.'); valid = false; }
            if (!valid) return;

            const dosis = {};
            form.querySelectorAll('[data-dosis]').forEach(inp => {
                if (inp.value.trim()) dosis[inp.dataset.dosis] = inp.value.trim();
            });

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.vaksinasi.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        burung_ids: catatTargetIds,
                        jenis: jenis,
                        metode: form.metode.value,
                        tanggal_vaksin: form.tanggal_vaksin.value,
                        dosis: dosis,
                        catatan: form.catatan.value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) { showToast('error', 'Gagal', data.message || 'Gagal mencatat.'); return; }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalCatat');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        // ===== Hapus =====
        function bukaModalHapus(id, jenis, burung) {
            hapusTargetId = id;
            document.getElementById('hapus_jenis').textContent = jenis;
            document.getElementById('hapus_burung').textContent = burung;
            bukaModal('modalHapus');
        }

        document.getElementById('btnHapus').addEventListener('click', async function () {
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.vaksinasi.destroy', ':id') }}'.replace(':id', hapusTargetId), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) { showToast('error', 'Gagal', data.message || 'Gagal menghapus catatan.'); return; }
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
