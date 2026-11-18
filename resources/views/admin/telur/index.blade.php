@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Telur & Incubator</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sesi bertelur, perebut, usia harian, sampai siap dilepas (±3 bulan)</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="eksporExcel(this, { namaFile: 'laporan_telur_incubator_{{ now()->toDateString() }}.xlsx', judul: 'Laporan Telur', headers: ['Induk (Pasangan)', 'Sesi', 'Jumlah Butir', 'Diambil', 'Diambil Oleh', 'Usia/Keterangan', 'Kondisi', 'Status'], rows: DATA_EKSPOR })"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                Export Excel
            </button>
            <a href="{{ route('admin.telur.export-pdf') }}"
                class="px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                Export PDF
            </a>
            <button onclick="bukaModalTambah()"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                + Catat Telur
            </button>
        </div>
    </div>

    @php
        $diinkubasi = $telurs->where('status', 'diinkubasi');
        $menetas = $telurs->where('status', 'menetas');
        $siapDilepas = $menetas->filter(fn ($t) => $t->siap_dilepas);
        // Laporan WA dikelompokkan per PASANGAN induk; catatan lama satu induk tetap digroup sendiri.
        $perInduk = $diinkubasi->groupBy(fn ($t) => $t->pasangan ? 'p-'.$t->burung_id.'-'.$t->burung_pasangan_id : 's-'.$t->burung_id);
    @endphp

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Telur Diinkubasi</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums mt-1">{{ $diinkubasi->sum('jumlah') }} <span class="text-sm font-semibold text-gray-500">butir ({{ $diinkubasi->count() }} catatan)</span></p>
        </div>
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Menetas (Incubator)</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums mt-1">{{ $menetas->count() }} <span class="text-sm font-semibold text-gray-500">catatan</span></p>
        </div>
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Siap Dilepas (±90 hari)</p>
            <p class="text-2xl font-bold {{ $siapDilepas->isNotEmpty() ? 'text-green-600' : 'text-gray-900 dark:text-white' }} tabular-nums mt-1">{{ $siapDilepas->count() }}</p>
        </div>
    </div>

    {{-- Laporan perkembangan per induk (siap salin ke WA group) --}}
    @if ($perInduk->isNotEmpty())
        <div class="mb-8 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Laporan Perkembangan Telur (siap salin ke WA group)</h3>
            </div>
            <div class="p-4 space-y-3">
                @foreach ($perInduk as $grup)
                    @php
                        $telurPertama = $grup->sortBy('tanggal_diambil')->first();
                    @endphp
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 bg-gray-50 dark:bg-[#151a22] rounded-xl">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $telurPertama->induk_label }} <span class="text-xs font-semibold text-gray-500">· {{ $grup->sum('jumlah') }} butir ({{ $grup->count() }} sesi)</span></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @foreach ($grup->sortBy('tanggal_diambil') as $t)
                                    Sesi {{ $t->sesi }}: {{ $t->usia_hari }} hari ({{ $t->kondisi }})@if (! $loop->last) · @endif
                                @endforeach
                            </p>
                        </div>
                        <button type="button" onclick='salinLaporan(this)'
                            data-nama='{{ $telurPertama->induk_label }}'
                            data-rows='{{ json_encode($grup->sortBy("tanggal_diambil")->map(fn ($t) => ["sesi" => $t->sesi, "kondisi" => $t->kondisi, "usia" => $t->usia_hari])->values()) }}'
                            class="self-start px-3 py-2 text-xs font-semibold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors">
                            Salin laporan WA
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Riwayat --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <x-tabler-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
            <input type="text" class="table-search-input w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari induk / sesi / kondisi...">
        </div>
    </div>

    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4">Induk</th>
                        <th class="px-6 py-4 w-16">Sesi</th>
                        <th class="px-6 py-4 w-16">Butir</th>
                        <th class="px-6 py-4 w-36">Diambil</th>
                        <th class="px-6 py-4 w-24">Usia / Umur</th>
                        <th class="px-6 py-4">Kondisi</th>
                        <th class="px-6 py-4 w-28">Status</th>
                        <th class="px-6 py-4 w-32 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($telurs as $t)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $t->induk_label }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums">{{ $t->sesi }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums">{{ $t->jumlah }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="block tabular-nums">{{ $t->tanggal_diambil->translatedFormat('d M Y') }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">oleh {{ $t->diambil_oleh }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm tabular-nums">
                                @if ($t->status === 'menetas' && $t->tanggal_menetas)
                                    {{ $t->usia_menetas_hari }} hari
                                    @if ($t->siap_dilepas)
                                        <span class="block text-xs font-bold text-green-600">Siap dilepas</span>
                                    @endif
                                @else
                                    {{ $t->usia_hari }} hari
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $t->kondisi }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $t->status === 'diinkubasi' ? 'bg-blue-500/10 text-blue-500' : ($t->status === 'menetas' ? 'bg-purple-500/10 text-purple-500' : 'bg-gray-500/10 text-gray-500') }}">
                                    {{ ucfirst($t->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick='bukaModalEdit({{ json_encode([
                                        "id" => $t->id,
                                        "induk" => $t->induk_label,
                                        "sesi" => $t->sesi,
                                        "kondisi" => $t->kondisi,
                                        "status" => $t->status,
                                        "tanggal_menetas" => $t->tanggal_menetas?->toDateString(),
                                    ]) }})'
                                        class="px-3 py-1.5 text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors">
                                        Update
                                    </button>
                                    <button onclick='bukaModalHapus({{ $t->id }}, "{{ addslashes($t->induk_label) }}")'
                                        class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada catatan telur. Klik "+ Catat Telur" saat ada burung bertelur.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL CATAT TELUR ==================== --}}
    <div id="modalTambah" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Catat Telur Baru</h3>
                <button onclick="tutupModal('modalTambah')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form id="formTambah" onsubmit="submitTambah(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Kandang (pasangan induk - 2 burung otomatis tercatat)</label>
                    <select name="kandang_pasangan" id="tambah_kandang" onchange="tampilkanPasangan()"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <option value="">- Pilih kandang -</option>
                        @foreach ($kandangPasangan as $i => $kp)
                            <option value="{{ $i }}">Kandang {{ $kp['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_burung"></p>
                    <div id="info_pasangan" class="hidden mt-2 px-3 py-2 text-sm rounded-xl bg-blue-500/10 border border-blue-500/20 text-gray-900 dark:text-white">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-500 mr-2">Pasangan</span>
                        <span id="pasangan_label" class="font-semibold"></span>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Sesi ke-</label>
                        <input type="number" name="sesi" id="tambah_sesi" min="1" max="20" value="1"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_sesi"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jumlah Butir</label>
                        <input type="number" name="jumlah" id="tambah_jumlah" min="1" max="20" value="1"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_jumlah"></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tanggal Diambil</label>
                        <input type="date" name="tanggal_diambil" id="tambah_tanggal" max="{{ now('Asia/Jakarta')->toDateString() }}"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_tanggal"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Diambil Oleh</label>
                        <input type="text" name="diambil_oleh" id="tambah_oleh" placeholder="Nama pekerja"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_oleh"></p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Kondisi Telur</label>
                    <input type="text" name="kondisi" id="tambah_kondisi" value="Kondisi telur baik"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_kondisi"></p>
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

    {{-- ==================== MODAL UPDATE TELUR ==================== --}}
    <div id="modalEdit" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Update Telur <span id="edit_induk_label" class="text-[#E62C37]"></span></h3>
                <button onclick="tutupModal('modalEdit')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form id="formEdit" onsubmit="submitEdit(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Kondisi Telur / Janin</label>
                    <input type="text" name="kondisi" id="edit_kondisi"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_kondisi"></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Status</label>
                        <select name="status" id="edit_status" onchange="toggleMenetas()"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="diinkubasi">Diinkubasi</option>
                            <option value="menetas">Menetas (masuk incubator)</option>
                            <option value="dilepas">Sudah dilepas</option>
                        </select>
                    </div>
                    <div id="edit_menetas_wrap" class="hidden">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tanggal Menetas</label>
                        <input type="date" name="tanggal_menetas" id="edit_menetas" max="{{ now('Asia/Jakarta')->toDateString() }}"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalEdit')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnEdit"
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
                    <x-tabler-alert-triangle class="w-5 h-5 text-[#E62C37]" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Catatan Telur</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hapus catatan telur milik <span id="hapus_induk" class="font-semibold text-gray-700 dark:text-gray-200"></span>?</p>
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
        // Daftar kandang pasangan (server-side): [{label, burung: [{id,nama,spesies,gender} × 2]}]
        // Data ekspor Excel (seluruh catatan telur, server-side)
        $dataEkspor = $telurs->map(fn ($t) => [
            $t->induk_label,
            (int) $t->sesi,
            (int) $t->jumlah,
            $t->tanggal_diambil->translatedFormat('d M Y'),
            $t->diambil_oleh,
            $t->status === 'menetas' && $t->tanggal_menetas
                ? $t->usia_menetas_hari.' hari'.($t->siap_dilepas ? ' (siap dilepas)' : '')
                : $t->usia_hari.' hari',
            $t->kondisi,
            ucfirst($t->status),
        ])->values()->all();
    @endphp
    <script>
        var CSRF = document.querySelector('meta[name="csrf-token"]').content;
        var ORDINAL = { 1: 'pertama', 2: 'kedua', 3: 'ketiga', 4: 'keempat', 5: 'kelima' };
        var PASANGAN = @json($kandangPasangan);
        {{-- Hex-escape: input admin (kondisi/keterangan) tak boleh bisa memutus konteks <script> --}}
        var DATA_EKSPOR = @json($dataEkspor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        var sedangBusy = false;
        var editTargetId = null;
        var hapusTargetId = null;

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnTambah, #btnEdit, #btnHapus').forEach(btn => {
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
            document.getElementById('tambah_kandang').value = '';
            document.getElementById('info_pasangan').classList.add('hidden');
            bukaModal('modalTambah');
        }

        // Pilih kandang → 2 induk di dalamnya otomatis tampil (keduanya langsung tercatat saat simpan)
        function tampilkanPasangan() {
            const idx = document.getElementById('tambah_kandang').value;
            const info = document.getElementById('info_pasangan');
            const kp = PASANGAN[idx];
            if (!kp) { info.classList.add('hidden'); return; }
            document.getElementById('pasangan_label').textContent = kp.burung[0].nama + ' (' + kp.burung[0].spesies + (kp.burung[0].gender ? ' - ' + kp.burung[0].gender : '') + ') × ' + kp.burung[1].nama + ' (' + kp.burung[1].spesies + (kp.burung[1].gender ? ' - ' + kp.burung[1].gender : '') + ')';
            info.classList.remove('hidden');
        }

        // Salin laporan perkembangan telur — format WA group petinggi 4PUTRA
        async function salinLaporan(btn) {
            const rows = JSON.parse(btn.dataset.rows);
            const sekarang = new Date();
            const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][sekarang.getDay()];
            const tanggal = sekarang.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            const kondisi = rows[0]?.kondisi || 'Kondisi telur baik';
            let teks = `Update perkembangan telur ${btn.dataset.nama}\n${hari}, ${tanggal}\n${kondisi}\n\nUsia telur kurang lebih\n`;
            rows.forEach((r, i) => {
                teks += `* Telur ${ORDINAL[r.sesi] || 'ke-' + r.sesi} ${r.usia} hari\n`;
            });
            try {
                await navigator.clipboard.writeText(teks);
                showToast('success', 'Tersalin', 'Laporan WA siap ditempel ke group.');
            } catch {
                showToast('error', 'Gagal', 'Browser tidak mengizinkan salin otomatis.');
            }
        }

        async function submitTambah(e) {
            e.preventDefault();
            const form = document.getElementById('formTambah');
            bersihkanError(form);
            let valid = true;
            const kp = PASANGAN[form.kandang_pasangan.value];
            if (!kp) { tandaiError('tambah_kandang', 'err_tambah_burung', 'Pilih kandang pasangan terlebih dahulu.'); valid = false; }
            if (!form.sesi.value || Number(form.sesi.value) < 1) { tandaiError('tambah_sesi', 'err_tambah_sesi', 'Sesi minimal 1.'); valid = false; }
            if (!form.jumlah.value || Number(form.jumlah.value) < 1) { tandaiError('tambah_jumlah', 'err_tambah_jumlah', 'Jumlah butir minimal 1.'); valid = false; }
            if (!form.tanggal_diambil.value) { tandaiError('tambah_tanggal', 'err_tambah_tanggal', 'Tanggal diambil wajib diisi.'); valid = false; }
            if (!form.diambil_oleh.value.trim()) { tandaiError('tambah_oleh', 'err_tambah_oleh', 'Nama pekerja wajib diisi.'); valid = false; }
            if (!form.kondisi.value.trim()) { tandaiError('tambah_kondisi', 'err_tambah_kondisi', 'Kondisi telur wajib diisi.'); valid = false; }
            if (!valid) return;

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.telur.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        burung_id: kp.burung[0].id,
                        burung_pasangan_id: kp.burung[1].id,
                        sesi: Number(form.sesi.value),
                        jumlah: Number(form.jumlah.value),
                        tanggal_diambil: form.tanggal_diambil.value,
                        diambil_oleh: form.diambil_oleh.value.trim(),
                        kondisi: form.kondisi.value.trim(),
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal mencatat telur.');
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

        function bukaModalEdit(data) {
            editTargetId = data.id;
            bersihkanError(document.getElementById('formEdit'));
            document.getElementById('edit_induk_label').textContent = data.induk + ' (sesi ' + data.sesi + ')';
            document.getElementById('edit_kondisi').value = data.kondisi;
            document.getElementById('edit_status').value = data.status;
            document.getElementById('edit_menetas').value = data.tanggal_menetas || '';
            toggleMenetas();
            bukaModal('modalEdit');
        }

        function toggleMenetas() {
            const status = document.getElementById('edit_status').value;
            document.getElementById('edit_menetas_wrap').classList.toggle('hidden', status !== 'menetas');
        }

        async function submitEdit(e) {
            e.preventDefault();
            const form = document.getElementById('formEdit');
            bersihkanError(form);
            if (!form.kondisi.value.trim()) { tandaiError('edit_kondisi', 'err_edit_kondisi', 'Kondisi wajib diisi.'); return; }

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.telur.update', ':id') }}'.replace(':id', editTargetId), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        kondisi: form.kondisi.value.trim(),
                        status: form.status.value,
                        tanggal_menetas: form.status.value === 'menetas' ? (form.tanggal_menetas.value || null) : null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal memperbarui data telur.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalEdit');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        function bukaModalHapus(id, induk) {
            hapusTargetId = id;
            document.getElementById('hapus_induk').textContent = induk;
            bukaModal('modalHapus');
        }

        document.getElementById('btnHapus').addEventListener('click', async function () {
            if (!hapusTargetId || sedangBusy) return;
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.telur.destroy', ':id') }}'.replace(':id', hapusTargetId), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus catatan telur.');
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
