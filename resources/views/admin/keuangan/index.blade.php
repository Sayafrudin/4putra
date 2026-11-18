@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Keuangan Farm</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pemasukan, pengeluaran, gaji bulanan, dan budget pembangunan</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="eksporExcel(this, { namaFile: eksporNamaFile(), judul: eksporJudul(), headers: ['Tanggal', 'Tipe', 'Kategori', 'Jumlah (Rp)', 'Keterangan'], rows: DATA_EKSPOR, ringkasan: { rows: RINGKASAN_EKSPOR } })"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                Export Excel
            </button>
            <a href="{{ route('admin.keuangan.export-pdf', array_filter(['tipe' => $tipeAktif, 'kategori' => $kategoriAktif, 'tab' => $tabAktif?->id])) }}"
                class="px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                Export PDF
            </a>
            <button onclick="bukaModalTransfer()"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">
                + Transfer Dana
            </button>
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

    {{-- ==================== BAR TAB (Kas Umum + tab proyek) ==================== --}}
    <div class="flex items-end gap-1 mb-6 overflow-x-auto">
        <a href="{{ route('admin.keuangan.index') }}" data-turbo="true"
            class="px-4 py-2.5 text-sm font-semibold whitespace-nowrap rounded-t-xl border border-b-0 transition-colors {{ $tabAktif ? 'bg-white dark:bg-[#151a22] border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200' : 'bg-white dark:bg-[#1e2530] border-gray-200 dark:border-gray-700 text-[#E62C37] font-bold' }}">
            Kas Umum
        </a>
        @foreach ($tabs as $t)
            <a href="{{ route('admin.keuangan.index', ['tab' => $t->id]) }}" data-turbo="true"
                class="px-4 py-2.5 text-sm font-semibold whitespace-nowrap rounded-t-xl border border-b-0 transition-colors {{ $tabAktif?->id == $t->id ? 'bg-white dark:bg-[#1e2530] border-gray-200 dark:border-gray-700 text-[#E62C37] font-bold' : 'bg-white dark:bg-[#151a22] border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200' }}">
                {{ $t->nama }}
            </a>
            @if ($tabAktif?->id == $t->id)
                <button type="button" onclick="bukaModalTabRename()" title="Ganti nama tab"
                    class="px-2 py-2.5 text-gray-400 hover:text-gray-200 transition-colors">
                    <x-tabler-pencil class="w-4 h-4" />
                </button>
                <button type="button" onclick="bukaModalTabHapus()" title="Hapus tab"
                    class="px-2 py-2.5 text-gray-400 hover:text-[#E62C37] transition-colors">
                    <x-tabler-trash class="w-4 h-4" />
                </button>
            @endif
        @endforeach
        <button type="button" onclick="bukaModal('modalTabBaru')" title="Tab proyek baru"
            class="px-3 py-2.5 text-lg leading-none font-bold text-gray-500 dark:text-gray-400 hover:text-[#E62C37] border border-dashed border-gray-300 dark:border-gray-700 rounded-t-xl transition-colors">
            +
        </button>
    </div>

    {{-- ==================== RINGKASAN ==================== --}}
    <div class="grid grid-cols-2 {{ $tabAktif ? 'sm:grid-cols-4' : 'sm:grid-cols-3' }} gap-4 mb-6">
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Masuk</p>
            <p class="text-xl font-bold text-green-600 tabular-nums mt-1">{{ number_format((float) $ringkasan['masuk'], 0, ',', '.') }}</p>
        </div>
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Keluar</p>
            <p class="text-xl font-bold text-[#E62C37] tabular-nums mt-1">{{ number_format((float) $ringkasan['keluar'], 0, ',', '.') }}</p>
        </div>
        @if ($tabAktif)
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Budget</p>
                <p class="text-xl font-bold text-blue-500 tabular-nums mt-1" id="cardBudgetVal">{{ number_format((float) $totalBudget, 0, ',', '.') }}</p>
            </div>
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sisa Budget</p>
                <p class="text-xl font-bold tabular-nums mt-1 {{ $sisaBudget >= 0 ? 'text-gray-900 dark:text-white' : 'text-[#E62C37]' }}" id="cardSisaVal">{{ number_format((float) $sisaBudget, 0, ',', '.') }}</p>
            </div>
        @else
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</p>
                <p class="text-xl font-bold tabular-nums mt-1 {{ $ringkasan['saldo'] >= 0 ? 'text-gray-900 dark:text-white' : 'text-[#E62C37]' }}">{{ number_format((float) $ringkasan['saldo'], 0, ',', '.') }}</p>
            </div>
        @endif
    </div>

    {{-- ==================== PANEL BUDGET (khusus tab proyek) ==================== --}}
    @if ($tabAktif)
        <div class="w-full bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl p-4 sm:p-6 mb-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Target Anggaran & Rincian Biaya — {{ $tabAktif->nama }}</h3>

            {{-- Target anggaran utama --}}
            <div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-5">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Target Anggaran Utama (Rp)</label>
                    <input type="number" name="target_anggaran" id="budget_target" min="0" step="1000" value="{{ $tabAktif->target_anggaran }}"
                        class="w-full sm:w-72 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm tabular-nums text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_budget"></p>
                </div>
                <button type="button" id="btnBudget" onclick="simpanBudget()"
                    class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                    Simpan Target
                </button>
            </div>

            {{-- Rincian biaya tambahan --}}
            <form onsubmit="submitBiaya(event)" class="flex flex-col sm:flex-row sm:items-end gap-3 mb-4" novalidate>
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Biaya Tambahan</label>
                    <input type="text" id="biaya_nama" placeholder="Misal: Material kandang, Ongkir material"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_biaya_nama"></p>
                </div>
                <div class="sm:w-48">
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nominal (Rp)</label>
                    <input type="number" id="biaya_nominal" min="0" step="1000" placeholder="0"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm tabular-nums text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_biaya_nominal"></p>
                </div>
                <button type="submit" id="btnBiaya"
                    class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                    Tambah
                </button>
            </form>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3">Nama Biaya</th>
                            <th class="px-4 py-3 w-48">Nominal</th>
                            <th class="px-4 py-3 w-44 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="daftarBiaya" class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($tabAktif->costs as $c)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $c->nama }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums">Rp {{ number_format((float) $c->nominal, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" onclick='isiEditBiaya({{ $c->id }}, {{ json_encode($c->nama, JSON_HEX_APOS | JSON_HEX_QUOT) }}, {{ $c->nominal }})'
                                        class="px-3 py-1.5 text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 transition-colors">
                                        Edit
                                    </button>
                                    <button type="button" onclick='bukaModalHapusBiaya({{ $c->id }}, {{ json_encode($c->nama, JSON_HEX_APOS | JSON_HEX_QUOT) }})'
                                        class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 transition-colors">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada biaya tambahan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                Total Budget: <span class="font-bold text-gray-900 dark:text-white" id="totalBudgetPanel">Rp {{ number_format((float) $totalBudget, 0, ',', '.') }}</span>
                <span class="mx-1">·</span> Sisa Budget: <span class="font-bold text-gray-900 dark:text-white" id="sisaBudgetPanel">Rp {{ number_format((float) $sisaBudget, 0, ',', '.') }}</span>
            </p>
        </div>
    @endif

    {{-- ==================== FILTER ==================== --}}
    <form method="GET" action="{{ route('admin.keuangan.index') }}" class="flex flex-wrap items-end gap-3 mb-6">
        @if ($tabAktif)
            <input type="hidden" name="tab" value="{{ $tabAktif->id }}">
        @endif
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
        @if ($tipeAktif || $kategoriAktif || $tabAktif)
            <a href="{{ $tabAktif ? route('admin.keuangan.index', ['tab' => $tabAktif->id]) : route('admin.keuangan.index') }}"
                class="px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">Reset</a>
        @endif
    </form>

    {{-- Input search --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <x-tabler-search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
            <input type="text" class="table-search-input w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari transaksi...">
        </div>
    </div>

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
                                @if (! $t->transfer_id)
                                    <button onclick='bukaModalEdit({{ $t->id }}, "{{ $t->tipe }}", "{{ addslashes($t->kategori) }}", "{{ $t->jumlah }}", "{{ $t->tanggal->toDateString() }}", "{{ addslashes($t->keterangan ?? '') }}")'
                                        class="px-3 py-1.5 text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors mr-1.5">
                                        Edit
                                    </button>
                                @endif
                                <button onclick='bukaModalHapus({{ $t->id }}, "{{ addslashes($t->jumlah_format) }}", "{{ addslashes($t->kategori) }}")'
                                    class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">{{ $tabAktif ? 'Belum ada transaksi pada tab ini.' : 'Belum ada transaksi keuangan.' }}</td>
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
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form id="formTambah" onsubmit="submitTambah(event)" class="space-y-4" novalidate>
                @csrf
                <input type="hidden" name="tipe" id="tambah_tipe" value="masuk">
                <input type="hidden" name="financial_tab_id" id="tambah_tab" value="{{ $tabAktif?->id ?? '' }}">
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
                    <input type="date" name="tanggal" id="tambah_tanggal" max="{{ now('Asia/Jakarta')->toDateString() }}"
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

    {{-- ==================== MODAL TRANSFER DANA ==================== --}}
    <div id="modalTransfer" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Transfer <span class="text-indigo-500">Dana</span> Antar-Tab</h3>
                <button onclick="tutupModal('modalTransfer')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form onsubmit="submitTransfer(event)" class="space-y-4" novalidate>
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Dari</label>
                        <select id="transfer_dari"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="kas_umum">Kas Umum</option>
                            @foreach ($tabs as $t)
                                <option value="{{ $t->id }}">{{ $t->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Ke</label>
                        <select id="transfer_ke"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="" disabled selected>Pilih tujuan...</option>
                            @foreach ($tabs as $t)
                                <option value="{{ $t->id }}">{{ $t->nama }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_transfer_ke"></p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nominal (Rp)</label>
                    <input type="number" id="transfer_nominal" min="1" step="1000" placeholder="0"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm tabular-nums text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_transfer_nominal"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Keterangan (opsional)</label>
                    <textarea id="transfer_ket" rows="2" placeholder="Misal: dana untuk bayar material pembangunan"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none"></textarea>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Dua catatan dibuat sekaligus: keluar di tab asal & masuk di tab tujuan — menghapus salah satunya menghapus keduanya.</p>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalTransfer')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnTransfer"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">
                        Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL EDIT TRANSAKSI ==================== --}}
    <div id="modalEdit" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Transaksi</h3>
                <button onclick="tutupModal('modalEdit')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form onsubmit="submitEdit(event)" class="space-y-4" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tipe</label>
                    <select id="edit_tipe"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <option value="masuk">Masuk</option>
                        <option value="keluar">Keluar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Kategori</label>
                    <select id="edit_kategori"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        @foreach ($kategoriList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Jumlah (Rp)</label>
                    <input type="number" id="edit_jumlah" min="1" step="1000" placeholder="0"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm tabular-nums text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_jumlah"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tanggal</label>
                    <input type="date" id="edit_tanggal" max="{{ now('Asia/Jakarta')->toDateString() }}"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_tanggal"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Keterangan (opsional)</label>
                    <textarea id="edit_ket" rows="2"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalEdit')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnEdit"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL HAPUS TRANSAKSI ==================== --}}
    <div id="modalHapus" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-[#E62C37]/10 flex items-center justify-center">
                    <x-tabler-alert-triangle class="w-5 h-5 text-[#E62C37]" />
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

    {{-- ==================== MODAL TAB BARU ==================== --}}
    <div id="modalTabBaru" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tab Proyek Baru</h3>
                <button onclick="tutupModal('modalTabBaru')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form onsubmit="submitTabBaru(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Proyek</label>
                    <input type="text" id="tab_nama_baru" placeholder="Misal: Pembangunan Farm"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tab_baru"></p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalTabBaru')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnTabBaru"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                        Buat Tab
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL RENAME TAB ==================== --}}
    <div id="modalTabRename" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ganti Nama Tab</h3>
                <button onclick="tutupModal('modalTabRename')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form onsubmit="submitTabRename(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Proyek</label>
                    <input type="text" id="tab_nama_rename" value="{{ $tabAktif?->nama }}"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tab_rename"></p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalTabRename')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnTabRename"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL HAPUS TAB ==================== --}}
    <div id="modalTabHapus" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-[#E62C37]/10 flex items-center justify-center">
                    <x-tabler-alert-triangle class="w-5 h-5 text-[#E62C37]" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Tab</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hapus tab <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $tabAktif?->nama }}</span>?@if($jumlahTransaksi > 0) Tab ini masih berisi <span class="font-semibold text-[#E62C37]">{{ $jumlahTransaksi }} transaksi</span>. @endif Rincian biaya & target anggaran ikut terhapus bersama tab.</p>
                </div>
            </div>
            <div class="flex flex-col gap-3 mt-6">
                @if ($jumlahTransaksi > 0)
                    <button type="button" id="btnTabHapusSemua"
                        class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                        Hapus Semua ({{ $jumlahTransaksi }} transaksi) + Tab
                    </button>
                @endif
                <button type="button" id="btnTabHapus"
                    class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-gray-600 hover:bg-gray-700 rounded-xl transition-colors {{ $jumlahTransaksi > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $jumlahTransaksi > 0 ? 'disabled' : '' }}>
                    Hapus Tab (kosong)
                </button>
                <button type="button" onclick="tutupModal('modalTabHapus')"
                    class="w-full px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                    Batal
                </button>
            </div>
        </div>
    </div>

    {{-- ==================== MODAL HAPUS BIAYA ==================== --}}
    <div id="modalHapusBiaya" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-[#E62C37]/10 flex items-center justify-center">
                    <x-tabler-alert-triangle class="w-5 h-5 text-[#E62C37]" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Biaya</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hapus <span id="hapus_biaya_info" class="font-semibold text-gray-700 dark:text-gray-200"></span> dari rincian biaya?</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="tutupModal('modalHapusBiaya')"
                    class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                    Batal
                </button>
                <button type="button" id="btnHapusBiaya"
                    class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    @php
        // Data ekspor Excel: SELURUH baris sesuai filter & tab aktif (bukan hanya halaman paginasi).
        // Ringkasan dipisah sebagai blok tersendiri (dicetak rapi di bawah tabel oleh admin-export.js).
        // Tab proyek menyertakan target anggaran, biaya tambahan, total & sisa budget.
        $dataEkspor = $semuaTransaksi->map(fn ($trx) => [
            $trx->tanggal->translatedFormat('d M Y'),
            ucfirst($trx->tipe),
            $trx->kategori,
            (float) $trx->jumlah,
            $trx->keterangan ?? '',
        ])->values()->all();

        $ringkasanEkspor = [
            ['TOTAL MASUK', (float) $ringkasan['masuk']],
            ['TOTAL KELUAR', (float) $ringkasan['keluar']],
            ['SALDO', (float) $ringkasan['saldo']],
        ];

        if ($tabAktif) {
            $ringkasanEkspor[] = ['TARGET ANGGARAN UTAMA', (float) $tabAktif->target_anggaran];
            foreach ($tabAktif->costs as $c) {
                $ringkasanEkspor[] = ['BIAYA: '.$c->nama, (float) $c->nominal];
            }
            $ringkasanEkspor[] = ['TOTAL BUDGET', (float) $totalBudget];
            $ringkasanEkspor[] = ['SISA BUDGET', (float) $sisaBudget];
        }
    @endphp
    <script>
        var CSRF = document.querySelector('meta[name="csrf-token"]').content;
        {{-- Hex-escape: input admin tak boleh bisa memutus konteks <script> --}}
        var DATA_EKSPOR = @json($dataEkspor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        var RINGKASAN_EKSPOR = @json($ringkasanEkspor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        var TAB_ID = {{ $tabAktif?->id ?? 'null' }};
        var TAB_KELUAR = {{ (float) $ringkasan['keluar'] }};
        var biayaEditId = null;
        var hapusBiayaId = null;
        var hapusTargetId = null;
        var sedangBusy = false;

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnTambah, #btnHapus, #btnEdit, #btnBudget, #btnBiaya, #btnTabBaru, #btnTabRename, #btnTabHapus, #btnTabHapusSemua, #btnHapusBiaya').forEach(btn => {
                if (!btn) return;
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

        function formatAngka(n) {
            return Number(n).toLocaleString('id-ID');
        }

        function eksporNamaFile() {
            return 'laporan_keuangan' + (TAB_ID ? '_tab_' + TAB_ID : '') + '_{{ now()->toDateString() }}.xlsx';
        }

        function eksporJudul() {
            return 'Laporan Keuangan' + (TAB_ID ? ' — Tab ' + TAB_ID : '');
        }

        function tandaiError(inputId, errId, pesan) {
            const input = document.getElementById(inputId);
            if (input) input.classList.add('border-red-400');
            const err = document.getElementById(errId);
            if (err) { err.textContent = pesan; err.classList.remove('hidden'); }
        }

        function bersihkanError(pasangan) {
            pasangan.forEach(([inputId, errId]) => {
                const input = document.getElementById(inputId);
                if (input) input.classList.remove('border-red-400');
                const err = document.getElementById(errId);
                if (err) err.classList.add('hidden');
            });
        }

        // ===== Transaksi =====
        function bukaModalTambah(tipe) {
            bersihkanError([['tambah_jumlah', 'err_tambah_jumlah'], ['tambah_tanggal', 'err_tambah_tanggal']]);
            document.getElementById('tambah_tipe').value = tipe;
            document.getElementById('tambah_tipe_label').textContent = tipe === 'masuk' ? 'Masuk' : 'Keluar';
            bukaModal('modalTambah');
        }

        async function submitTambah(e) {
            e.preventDefault();
            const form = document.getElementById('formTambah');
            bersihkanError([['tambah_jumlah', 'err_tambah_jumlah'], ['tambah_tanggal', 'err_tambah_tanggal']]);
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
                        financial_tab_id: TAB_ID,
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

        // ===== Edit transaksi =====
        function bukaModalEdit(id, tipe, kategori, jumlah, tanggal, keterangan) {
            bersihkanError([['edit_jumlah', 'err_edit_jumlah'], ['edit_tanggal', 'err_edit_tanggal']]);
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_tipe').value = tipe;
            document.getElementById('edit_kategori').value = kategori;
            document.getElementById('edit_jumlah').value = Number(jumlah);
            document.getElementById('edit_tanggal').value = tanggal;
            document.getElementById('edit_ket').value = keterangan;
            bukaModal('modalEdit');
        }

        async function submitEdit(e) {
            e.preventDefault();
            bersihkanError([['edit_jumlah', 'err_edit_jumlah'], ['edit_tanggal', 'err_edit_tanggal']]);
            const jumlah = document.getElementById('edit_jumlah');
            const tanggal = document.getElementById('edit_tanggal');
            let valid = true;
            if (!jumlah.value || Number(jumlah.value) < 1) { tandaiError('edit_jumlah', 'err_edit_jumlah', 'Jumlah wajib diisi (minimal 1).'); valid = false; }
            if (!tanggal.value) { tandaiError('edit_tanggal', 'err_edit_tanggal', 'Tanggal wajib diisi.'); valid = false; }
            if (!valid) return;

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.update', ':id') }}'.replace(':id', document.getElementById('edit_id').value), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        tipe: document.getElementById('edit_tipe').value,
                        kategori: document.getElementById('edit_kategori').value,
                        jumlah: Number(jumlah.value),
                        tanggal: tanggal.value,
                        keterangan: document.getElementById('edit_ket').value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal memperbarui transaksi.');
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

        // ===== Tab proyek =====
        function bukaModalTabRename() { bersihkanError([['tab_nama_rename', 'err_tab_rename']]); bukaModal('modalTabRename'); }
        function bukaModalTabHapus() { bukaModal('modalTabHapus'); }

        async function submitTabBaru(e) {
            e.preventDefault();
            const input = document.getElementById('tab_nama_baru');
            bersihkanError([['tab_nama_baru', 'err_tab_baru']]);
            if (!input.value.trim()) { tandaiError('tab_nama_baru', 'err_tab_baru', 'Nama proyek wajib diisi.'); return; }
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.tabs.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ nama: input.value.trim() })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal membuat tab.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalTabBaru');
                window.Turbo ? Turbo.visit('{{ route('admin.keuangan.index') }}?tab=' + data.id) : window.location.href = '{{ route('admin.keuangan.index') }}?tab=' + data.id;
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        async function submitTabRename(e) {
            e.preventDefault();
            const input = document.getElementById('tab_nama_rename');
            bersihkanError([['tab_nama_rename', 'err_tab_rename']]);
            if (!input.value.trim()) { tandaiError('tab_nama_rename', 'err_tab_rename', 'Nama proyek wajib diisi.'); return; }

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.tabs.update', $tabAktif?->id ?? 0) }}', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ nama: input.value.trim() })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal mengubah nama tab.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalTabRename');
                window.Turbo ? Turbo.visit(window.location.href) : window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        document.getElementById('btnTabHapus').addEventListener('click', async function () {
            if (sedangBusy) return;
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.tabs.destroy', $tabAktif?->id ?? 0) }}', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus tab.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalTabHapus');
                window.Turbo ? Turbo.visit('{{ route('admin.keuangan.index') }}') : window.location.href = '{{ route('admin.keuangan.index') }}';
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        });

        // var (bukan const/let): script inline dieksekusi ulang Turbo pada tiap
        // navigasi — const top-level membuat binding global leksikal yang gagal
        // dideklarasikan ulang ("Identifier already declared") dan mematikan
        // seluruh script halaman berikutnya.
        var btnHapusSemua = document.getElementById('btnTabHapusSemua');
        if (btnHapusSemua) btnHapusSemua.addEventListener('click', async function () {
            if (sedangBusy) return;
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.tabs.destroy', $tabAktif?->id ?? 0) }}?hapus_semua=1', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus tab.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalTabHapus');
                window.Turbo ? Turbo.visit('{{ route('admin.keuangan.index') }}') : window.location.href = '{{ route('admin.keuangan.index') }}';
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        });

        // ===== Transfer dana antar-tab =====
        function bukaModalTransfer() {
            bersihkanError([['transfer_ke', 'err_transfer_ke'], ['transfer_nominal', 'err_transfer_nominal']]);
            bukaModal('modalTransfer');
        }

        async function submitTransfer(e) {
            e.preventDefault();
            const dari = document.getElementById('transfer_dari');
            const ke = document.getElementById('transfer_ke');
            const nominal = document.getElementById('transfer_nominal');
            bersihkanError([['transfer_ke', 'err_transfer_ke'], ['transfer_nominal', 'err_transfer_nominal']]);
            let valid = true;
            if (!ke.value) { tandaiError('transfer_ke', 'err_transfer_ke', 'Tab tujuan wajib dipilih.'); valid = false; }
            else if (ke.value === dari.value) { tandaiError('transfer_ke', 'err_transfer_ke', 'Tab asal dan tujuan tidak boleh sama.'); valid = false; }
            if (!nominal.value || Number(nominal.value) < 1) { tandaiError('transfer_nominal', 'err_transfer_nominal', 'Nominal wajib diisi (minimal 1).'); valid = false; }
            if (!valid) return;

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.transfer') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        dari: dari.value === 'kas_umum' ? 'kas_umum' : Number(dari.value),
                        ke: Number(ke.value),
                        nominal: Number(nominal.value),
                        keterangan: document.getElementById('transfer_ket').value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal mencatat transfer.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalTransfer');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        // ===== Budget & biaya (khusus tab proyek) =====
        function perbaruiTotalBudget(total) {
            const totalFmt = formatAngka(total);
            const sisa = total - TAB_KELUAR;
            const elBudget = document.getElementById('cardBudgetVal');
            const elSisa = document.getElementById('cardSisaVal');
            const elPanel = document.getElementById('totalBudgetPanel');
            const elSisaPanel = document.getElementById('sisaBudgetPanel');
            if (elBudget) elBudget.textContent = totalFmt;
            if (elSisa) { elSisa.textContent = formatAngka(sisa); elSisa.classList.toggle('text-[#E62C37]', sisa < 0); }
            if (elPanel) elPanel.textContent = 'Rp ' + totalFmt;
            if (elSisaPanel) elSisaPanel.textContent = 'Rp ' + formatAngka(sisa);
        }

        async function simpanBudget() {
            const input = document.getElementById('budget_target');
            bersihkanError([['budget_target', 'err_budget']]);
            if (input.value === '' || Number(input.value) < 0) { tandaiError('budget_target', 'err_budget', 'Target anggaran wajib diisi (minimal 0).'); return; }

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.tabs.budget', $tabAktif?->id ?? 0) }}', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ target_anggaran: Number(input.value) })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menyimpan target anggaran.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                perbaruiTotalBudget(data.total_budget);
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        function submitBiaya(e) {
            e.preventDefault();
            const nama = document.getElementById('biaya_nama');
            const nominal = document.getElementById('biaya_nominal');
            bersihkanError([['biaya_nama', 'err_biaya_nama'], ['biaya_nominal', 'err_biaya_nominal']]);
            let valid = true;
            if (!nama.value.trim()) { tandaiError('biaya_nama', 'err_biaya_nama', 'Nama biaya wajib diisi.'); valid = false; }
            if (nominal.value === '' || Number(nominal.value) < 0) { tandaiError('biaya_nominal', 'err_biaya_nominal', 'Nominal wajib diisi (minimal 0).'); valid = false; }
            if (!valid) return;

            return biayaEditId ? perbaruiBiaya() : tambahBiaya();
        }

        async function tambahBiaya() {
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.costs.store', $tabAktif?->id ?? 0) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        nama: document.getElementById('biaya_nama').value.trim(),
                        nominal: Number(document.getElementById('biaya_nominal').value),
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menambah biaya.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                perbaruiTotalBudget(data.total_budget);
                window.Turbo ? Turbo.visit(window.location.href, { action: 'replace' }) : window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        async function perbaruiBiaya() {
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.costs.update', ':id') }}'.replace(':id', biayaEditId), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        nama: document.getElementById('biaya_nama').value.trim(),
                        nominal: Number(document.getElementById('biaya_nominal').value),
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal mengubah biaya.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                perbaruiTotalBudget(data.total_budget);
                window.Turbo ? Turbo.visit(window.location.href, { action: 'replace' }) : window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        function isiEditBiaya(id, nama, nominal) {
            biayaEditId = id;
            document.getElementById('biaya_nama').value = nama;
            document.getElementById('biaya_nominal').value = Number(nominal);
            document.getElementById('btnBiaya').textContent = 'Simpan Perubahan';
            document.getElementById('biaya_nama').focus();
        }

        function bukaModalHapusBiaya(id, nama) {
            hapusBiayaId = id;
            document.getElementById('hapus_biaya_info').textContent = nama;
            bukaModal('modalHapusBiaya');
        }

        document.getElementById('btnHapusBiaya').addEventListener('click', async function () {
            if (!hapusBiayaId || sedangBusy) return;
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.keuangan.costs.destroy', ':id') }}'.replace(':id', hapusBiayaId), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus biaya.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalHapusBiaya');
                perbaruiTotalBudget(data.total_budget);
                window.Turbo ? Turbo.visit(window.location.href, { action: 'replace' }) : window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        });
    </script>
@endsection
