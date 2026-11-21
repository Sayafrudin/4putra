@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Obat & Vaksinasi</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Catat pemberian obat/vaksin — suntik 1x/tahun, oral tiap 3 bulan</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="eksporExcel(this, { namaFile: 'laporan_obat_vaksinasi_{{ now()->toDateString() }}.xlsx', judul: 'Laporan Obat & Vaksinasi', headers: ['Burung', 'Spesies', 'Jenis', 'Metode', 'Dosis', 'Tanggal', 'Status Ulang'], rows: DATA_EKSPOR })"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                Export Excel
            </button>
            <a href="{{ route('admin.vaksinasi.export-pdf') }}"
                class="px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-600 rounded-xl hover:border-gray-400 transition-colors">
                Export PDF
            </a>
    </div>
    </div>

    {{-- Panel jatuh tempo: siklus per metode (suntik 12 bulan, oral 3 bulan) --}}
    <div class="mb-8 bg-white dark:bg-[#1e2530] border {{ $jatuhTempo->isNotEmpty() ? 'border-[#E62C37]/40' : 'border-gray-700' }} rounded-xl shadow-sm">
        <div class="px-5 py-3 border-b border-gray-700 flex items-center justify-between gap-3">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-300">Perlu Ulang (Suntik/Oral)</h3>
            <span class="text-xs font-bold {{ $jatuhTempo->isNotEmpty() ? 'text-[#E62C37] bg-[#E62C37]/10' : 'text-green-600 bg-green-600/10' }} px-2.5 py-1 rounded-full">
                {{ $jatuhTempo->isNotEmpty() ? $jatuhTempo->count().' burung' : 'Aman' }}
            </span>
        </div>
        <div class="p-4">
            @forelse ($jatuhTempo as $v)
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 py-2.5 border-b border-gray-800 last:border-0">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-200">{{ $v->burung->nama }} @if ($v->burung->nomor_ring)<span class="text-xs text-gray-500">· {{ $v->burung->label_ring }}</span>@endif <span class="text-xs font-medium text-gray-500">· {{ $v->burung->spesies }}</span></p>
                        <p class="text-xs text-gray-500">terakhir "{{ $v->jenis }}" ({{ $v->metode === 'oral' ? 'Oral' : 'Suntik' }}) pada {{ $v->tanggal_vaksin->translatedFormat('d M Y') }}</p>
                    </div>
                    <span class="text-xs font-bold text-[#E62C37] bg-[#E62C37]/10 px-2.5 py-1 rounded-lg self-start">Jatuh tempo</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">Semua burung masih terlindungi sesuai siklus masing-masing.</p>
            @endforelse
        </div>
    </div>

    {{-- Papan ala kandang: section per blok, card per kandang, chip burung + badge status --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <x-tabler-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
            <input type="text" id="vaksin_search" placeholder="Cari kandang (contoh: E-3) atau nama burung..."
                class="w-full bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none">
        </div>
        <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-600 inline-block"></span> Masih terlindungi</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#E62C37] inline-block"></span> Jatuh tempo / belum dicatat</span>
            <span class="hidden sm:inline text-gray-600">Klik card kandang untuk detail riwayat &amp; catat per burung atau massal.</span>
        </div>
    </div>

    <div class="space-y-8 pb-10">
        @foreach ($perKandang as $blok => $grupKandang)
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Blok {{ $blok }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach ($grupKandang as $item)
                        @php
                            $kandang = $item['kandang'];
                            $burungList = $item['burung'];
                            $perlu = $burungList->filter(fn ($b) => !$terakhirPerBurung->has($b->id) || $terakhirPerBurung[$b->id]->sudah_jatuh_tempo)->count();
                        @endphp
                        <a href="{{ route('admin.vaksinasi.detail', $kandang) }}"
                            class="kandang-card block text-left bg-white dark:bg-[#1e2530] border {{ $perlu > 0 ? 'border-[#E62C37]/40' : 'border-gray-700' }} rounded-xl p-3 hover:border-[#E62C37]/60 transition-colors"
                            data-kandang-id="{{ $kandang->id }}" data-label="{{ $kandang->label }}">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $kandang->label }}</span>
                                <span class="whitespace-nowrap text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $perlu > 0 ? 'text-[#E62C37] bg-[#E62C37]/10' : 'text-green-600 bg-green-600/10' }}">
                                    {{ $perlu > 0 ? $perlu.' perlu obat/vaksin' : 'Aman' }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                @foreach ($burungList as $b)
                                    @php
                                        $terakhir = $terakhirPerBurung->get($b->id);
                                        $aman = $terakhir && !$terakhir->sudah_jatuh_tempo;
                                    @endphp
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-2 h-2 rounded-full shrink-0 {{ $aman ? 'bg-green-600' : 'bg-[#E62C37]' }}"></span>
                                        <span role="button" tabindex="0" onclick="event.preventDefault(); bukaDetailBurung({{ $b->id }})"
                                            class="font-semibold text-gray-800 dark:text-gray-200 truncate cursor-pointer hover:text-[#E62C37] transition-colors"
                                            title="Lihat identitas burung">{{ $b->nama }}</span>
                                        @if ($b->nomor_ring)
                                            <span class="shrink-0 text-gray-400 dark:text-gray-500">{{ $b->label_ring }}</span>
                                        @endif
                                        <span class="text-gray-500 truncate ml-auto">{{ $terakhir?->jenis ?? 'belum dicatat' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if ($tanpaKandang->isNotEmpty())
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Belum Ditempatkan</h3>
                <div class="bg-white dark:bg-[#1e2530] border border-gray-700 rounded-xl p-3">
                    <div class="flex flex-col gap-1.5">
                        @foreach ($tanpaKandang as $b)
                            @php
                                $terakhir = $terakhirPerBurung->get($b->id);
                                $aman = $terakhir && !$terakhir->sudah_jatuh_tempo;
                            @endphp
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-2 h-2 rounded-full shrink-0 {{ $aman ? 'bg-green-600' : 'bg-[#E62C37]' }}"></span>
                                <span role="button" tabindex="0" onclick="bukaDetailBurung({{ $b->id }})"
                                    class="font-semibold text-gray-800 dark:text-gray-200 cursor-pointer hover:text-[#E62C37] transition-colors"
                                    title="Lihat identitas burung">{{ $b->nama }}</span>
                                @if ($b->nomor_ring)
                                    <span class="shrink-0 text-gray-400 dark:text-gray-500">{{ $b->label_ring }}</span>
                                @endif
                                <span class="text-gray-500 truncate">{{ $b->spesies }} · {{ $terakhir?->jenis ?? 'belum dicatat' }}</span>
                                <button onclick='bukaModalCatat([{{ $b->id }}])'
                                    class="ml-auto shrink-0 px-3 py-1.5 text-[11px] font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors">
                                    Catat
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>

    {{-- Riwayat --}}
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
                        <th class="px-6 py-4 w-32">Status Ulang</th>
                        <th class="px-6 py-4 w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($riwayat as $v)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $v->burung->nama }}</span>
                                @if ($v->burung->nomor_ring)
                                    <span class="text-xs text-gray-500">· {{ $v->burung->label_ring }}</span>
                                @endif
                                <span class="block text-xs text-gray-500">{{ $v->burung->spesies }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $v->jenis }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-bold {{ $v->metode === 'oral' ? 'bg-amber-500/10 text-amber-600' : 'bg-blue-500/10 text-blue-500' }}">
                                    {{ $v->metode === 'oral' ? 'Oral' : 'Suntik' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $v->dosis ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums">{{ $v->tanggal_vaksin->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $v->sudah_jatuh_tempo ? 'bg-[#E62C37]/10 text-[#E62C37]' : 'bg-green-500/10 text-green-600' }}">
                                    {{ $v->sudah_jatuh_tempo ? 'Perlu ulang' : 'Terlindungi' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick='bukaModalHapus({{ $v->id }}, "{{ addslashes($v->jenis) }}", "{{ addslashes($v->burung->nama) }}")'
                                        class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada catatan. Klik "+ Catat" atau buka card kandang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL CATAT (tunggal / massal) ==================== --}}
    <div id="modalCatat" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Catat Obat / Vaksin</h3>
                <button onclick="tutupModal('modalCatat')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <p id="catat_target" class="text-xs text-gray-500 mb-4"></p>
            <form id="formCatat" onsubmit="submitCatat(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jenis</label>
                    <select name="jenis_pilih" id="catat_jenis_pilih" onchange="ubahJenis()"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        @foreach (\App\Models\Vaksinasi::JENIS_STANDAR as $j)
                            <option value="{{ $j }}">{{ $j }}</option>
                        @endforeach
                        <option value="Lainnya">Lainnya (tulis sendiri)</option>
                    </select>
                    <input type="text" name="jenis" id="catat_jenis" placeholder="Tulis nama obat/vaksin..."
                        class="hidden w-full mt-2 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_catat_jenis"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Metode</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-start gap-2 p-3 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer hover:border-[#E62C37]/50 has-checked:border-[#E62C37]">
                            <input type="radio" name="metode" value="suntik" checked class="mt-1 accent-[#E62C37]">
                            <span>
                                <span class="block text-sm font-semibold text-gray-900 dark:text-white">Suntik</span>
                                <span class="block text-xs text-gray-500">1x per tahun (semua burung)</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 p-3 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer hover:border-[#E62C37]/50 has-checked:border-[#E62C37]">
                            <input type="radio" name="metode" value="oral" class="mt-1 accent-[#E62C37]">
                            <span>
                                <span class="block text-sm font-semibold text-gray-900 dark:text-white">Oral</span>
                                <span class="block text-xs text-gray-500">Tiap 3 bulan (campur makanan/minuman)</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tanggal Pemberian</label>
                    <input type="date" name="tanggal_vaksin" id="catat_tanggal" max="{{ now('Asia/Jakarta')->toDateString() }}"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_catat_tanggal"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Dosis (opsional, per burung)</label>
                    <div id="catat_dosis_rows" class="space-y-2"></div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Catatan (opsional)</label>
                    <textarea name="catatan" id="catat_catatan" rows="2" placeholder="Dokter hewan, reaksi, dll..."
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none"></textarea>
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
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-[#E62C37]/10 flex items-center justify-center shrink-0">
                    <x-tabler-alert-triangle class="w-5 h-5 text-[#E62C37]" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Catatan</h3>
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

    {{-- Modal identitas burung (klik nama burung di card/chip/tabel) --}}
    <x-admin.detail-burung :burungs="$burungSemua" />

    @php
        // Data papan untuk JS: satu kandang = daftar burung + catatan terakhirnya.
        $papanData = [];
        $mapBurung = function ($b) use ($terakhirPerBurung) {
            $terakhir = $terakhirPerBurung->get($b->id);

            return [
                'id' => $b->id,
                'nama' => $b->nama,
                'spesies' => $b->spesies,
                'ring' => $b->nomor_ring,
                'gender' => $b->gender_label,
                'terakhir' => $terakhir ? [
                    'jenis' => $terakhir->jenis,
                    'metode' => $terakhir->metode,
                    'dosis' => $terakhir->dosis,
                    'tanggal' => $terakhir->tanggal_vaksin->translatedFormat('d M Y'),
                    'jatuh_tempo' => $terakhir->sudah_jatuh_tempo,
                ] : null,
            ];
        };
        foreach ($perKandang as $grupKandang) {
            foreach ($grupKandang as $item) {
                $papanData[$item['kandang']->id] = [
                    'label' => $item['kandang']->label,
                    'burung' => $item['burung']->map($mapBurung)->values()->all(),
                ];
            }
        }
        // Burung tanpa kandang ikut dropdown manual catat (grup "Belum Ditempatkan")
        if ($tanpaKandang->isNotEmpty()) {
            $papanData[0] = ['label' => 'Belum Ditempatkan', 'burung' => $tanpaKandang->map($mapBurung)->values()->all()];
        }
        $dataEkspor = $riwayat->map(fn ($v) => [
            $v->burung->nama_ring,
            $v->burung->spesies,
            $v->jenis,
            $v->metode === 'oral' ? 'Oral' : 'Suntik',
            $v->dosis ?? '',
            $v->tanggal_vaksin->translatedFormat('d M Y'),
            $v->sudah_jatuh_tempo ? 'Perlu ulang' : 'Terlindungi',
        ])->values()->all();
    @endphp
    <script>
        var CSRF = document.querySelector('meta[name="csrf-token"]').content;
        {{-- Hex-escape: input admin (dosis/catatan) tak boleh memutus konteks script --}}
        var PAPAN = @json($papanData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        var DATA_EKSPOR = @json($dataEkspor, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        var sedangBusy = false;
        var hapusTargetId = null;
        var catatTargetIds = [];

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

        // ===== Modal catat (tunggal / massal) =====
        function ubahJenis() {
            const pilih = document.getElementById('catat_jenis_pilih');
            const input = document.getElementById('catat_jenis');
            const lainnya = pilih.value === 'Lainnya';
            input.classList.toggle('hidden', !lainnya);
            if (!lainnya) input.value = '';
        }

        function bukaModalCatat(ids) {
            catatTargetIds = ids;
            const wrap = document.getElementById('catat_dosis_rows');
            wrap.innerHTML = '';
            let label;
            if (ids.length === 0) {
                // Pilih manual dari dropdown semua burung
                label = 'Pilih burung di bawah (satu catatan per burung).';
                const sel = document.createElement('select');
                sel.id = 'catat_burung_manual';
                sel.className = 'w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:outline-none';
                sel.innerHTML = '<option value="">- Pilih burung -</option>';
                Object.values(PAPAN).forEach(function (k) {
                    k.burung.forEach(function (b) {
                        sel.add(new Option(labelBurung(b) + ' — ' + k.label, b.id));
                    });
                });
                wrap.appendChild(sel);
            } else {
                label = ids.length === 1 ? 'Target: 1 burung.' : 'Target: ' + ids.length + ' burung (catatan massal — satu klik).';
                const data = {};
                Object.values(PAPAN).forEach(function (k) {
                    k.burung.forEach(function (b) { data[b.id] = b; });
                });
                ids.forEach(function (id) {
                    const b = data[id];
                    if (!b) return;
                    const baris = document.createElement('div');
                    baris.className = 'flex items-center gap-2';
                    const label = document.createElement('span');
                    label.className = 'text-xs font-semibold text-gray-700 dark:text-gray-200 w-44 truncate';
                    label.title = labelBurung(b);
                    label.textContent = labelBurung(b);
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.dataset.dosis = id;
                    input.placeholder = 'Dosis (opsional)';
                    input.className = 'flex-1 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-gray-200 focus:border-[#E62C37] focus:outline-none';
                    baris.append(label, input);
                    wrap.appendChild(baris);
                });
            }
            document.getElementById('catat_target').textContent = label;
            bersihkanError(document.getElementById('formCatat'));
            document.getElementById('catat_tanggal').value = '';
            document.getElementById('catat_catatan').value = '';
            document.getElementById('catat_jenis_pilih').value = '{{ \App\Models\Vaksinasi::JENIS_STANDAR[0] }}';
            ubahJenis();
            bukaModal('modalCatat');
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

        async function submitCatat(e) {
            e.preventDefault();
            const form = e.target;
            bersihkanError(form);
            let valid = true;
            const pilih = document.getElementById('catat_jenis_pilih');
            const jenis = pilih.value === 'Lainnya' ? document.getElementById('catat_jenis').value.trim() : pilih.value;
            if (!jenis) { tandaiError('catat_jenis', 'err_catat_jenis', 'Nama obat/vaksin wajib diisi.'); valid = false; }
            const tanggal = document.getElementById('catat_tanggal').value;
            if (!tanggal) { tandaiError('catat_tanggal', 'err_catat_tanggal', 'Tanggal wajib diisi.'); valid = false; }
            if (!valid) return;

            const ids = catatTargetIds.length > 0 ? catatTargetIds : [document.getElementById('catat_burung_manual')?.value];
            if (!ids[0]) { showToast('error', 'Pilih burung dulu', 'Target catatan belum ada.'); return; }
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
                        burung_ids: ids,
                        jenis: jenis,
                        metode: form.metode.value,
                        tanggal_vaksin: tanggal,
                        dosis: dosis,
                        catatan: document.getElementById('catat_catatan').value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal mencatat. Coba lagi.');
                    setBusy(false);
                    return;
                }
                showToast('success', 'Tersimpan', data.message);
                tutupModal('modalCatat');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
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
                    setBusy(false);
                    return;
                }
                showToast('success', 'Terhapus', data.message);
                tutupModal('modalHapus');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
                setBusy(false);
            }
        });

        // ===== Search papan (sembunyikan card & section tanpa hasil) =====
        document.getElementById('vaksin_search').addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.kandang-card').forEach(function (card) {
                const cocok = !q || card.dataset.label.toLowerCase().includes(q) ||
                    (PAPAN[card.dataset.kandangId] || { burung: [] }).burung.some(function (b) {
                        return b.nama.toLowerCase().includes(q);
                    });
                card.style.display = cocok ? '' : 'none';
            });
            document.querySelectorAll('section').forEach(function (sec) {
                const cards = sec.querySelectorAll('.kandang-card');
                if (cards.length === 0) return;
                const ada = Array.from(cards).some(function (c) { return c.style.display !== 'none'; });
                sec.style.display = ada ? '' : 'none';
            });
        });
    </script>
@endsection
