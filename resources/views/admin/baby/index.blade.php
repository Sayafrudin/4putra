@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Baby</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Baby (loloh) umur ≥1 bulan — siap dijual atau dipindah ke kandang/aviary</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="bukaModalJual()"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                + Catat Penjualan
            </button>
        </div>
    </div>

    {{-- Daftar baby (desain beda dari kandang: kartu info, bisa digeser ke target di bawah) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-10" id="babyGrid">
        @forelse ($babies as $b)
            <div class="baby-card bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm cursor-grab active:cursor-grabbing"
                draggable="true" data-burung-id="{{ $b->id }}" data-nama="{{ $b->nama }}">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $b->nama }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $b->spesies }} · <span class="text-purple-500 font-semibold">Baby</span></p>
                    </div>
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold bg-purple-500/10 text-purple-500 tabular-nums">{{ $b->usia_hari !== null ? $b->usia_hari.' hari' : '-' }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5 text-xs mb-2">
                    @if ($b->nomor_ring)
                        <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 font-semibold">Ring: {{ $b->nomor_ring }}</span>
                    @endif
                    @if ($b->gender)
                        <span class="px-2 py-0.5 rounded-full bg-gray-500/10 text-gray-500 dark:text-gray-400 font-semibold">{{ $b->gender_label }}</span>
                    @endif
                    @if ($b->nomor_dna)
                        <span class="px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-500 font-semibold">DNA: {{ $b->nomor_dna }}</span>
                    @endif
                </div>
                @if ($b->induk_label)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1"><span class="font-semibold">Induk:</span> {{ $b->induk_label }}</p>
                @endif
                @if ($b->asal_label)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1"><span class="font-semibold">Asal:</span> {{ $b->asal_label }}</p>
                @endif
                @if ($b->catatan)
                    <p class="text-xs text-amber-500/90 mb-1">{{ $b->catatan }}</p>
                @endif
                <div class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button onclick='bukaModalEdit({{ json_encode([
                        "id" => $b->id,
                        "nama" => $b->nama,
                        "spesies" => $b->spesies,
                        "fase" => $b->fase,
                        "gender" => $b->gender,
                        "nomor_ring" => $b->nomor_ring,
                        "nomor_dna" => $b->nomor_dna,
                        "catatan" => $b->catatan,
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})'
                        class="px-3 py-1.5 text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors">Edit</button>
                    <button onclick="bukaModalJual({{ $b->id }})"
                        class="px-3 py-1.5 text-xs font-bold text-green-600 bg-green-500/10 border border-green-500/20 rounded-lg hover:bg-green-500/20 hover:border-green-500/40 transition-colors">Jual</button>
                    <button onclick="bukaModalPindah({{ $b->id }}, '{{ addslashes($b->nama) }}')"
                        class="px-3 py-1.5 text-xs font-bold text-purple-400 bg-purple-500/10 border border-purple-500/20 rounded-lg hover:bg-purple-500/20 hover:border-purple-500/40 transition-colors">Pindah</button>
                    <button onclick="bukaModalHapus({{ $b->id }}, '{{ addslashes($b->nama) }}')"
                        class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">Hapus</button>
                </div>
            </div>
        @empty
            <div class="md:col-span-2 xl:col-span-3 p-6 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl text-center text-gray-500 dark:text-gray-400 text-sm">
                Belum ada baby. Baby muncul otomatis dari menu Telur &amp; Incubator saat butir "Sudah dilepas" (umur ≥30 hari setelah menetas).
            </div>
        @endforelse
    </div>

    {{-- Target drag & drop: kandang & aviary (ringkas) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-3">Pindahkan ke Kandang</h3>
            <div class="space-y-3">
                @foreach ($kandangs as $blok => $kandangBlok)
                    <div>
                        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5">Blok {{ $blok }}</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach ($kandangBlok as $k)
                                @php($isi = $k->burungs->count())
                                <div class="target-drop bg-white dark:bg-[#151a22] border {{ $isi >= 2 ? 'border-[#E62C37]/40' : 'border-gray-200 dark:border-gray-700' }} rounded-xl p-2.5"
                                    data-kandang-id="{{ $k->id }}" data-kandang-label="{{ $k->label }}"
                                    @if ($isi >= 2) data-penuh="1" @endif>
                                    <div class="flex items-center justify-between text-[10px] font-bold mb-1">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $k->label }}</span>
                                        <span class="{{ $isi >= 2 ? 'text-[#E62C37]' : 'text-gray-400' }}">{{ $isi }}/2</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-snug truncate" title="{{ $k->burungs->pluck('nama')->implode(', ') }}">
                                        {{ $k->burungs->pluck('nama')->implode(', ') ?: 'Kosong' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-3">Pindahkan ke Aviary</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach ($aviaries as $k)
                    <div class="target-drop bg-white dark:bg-[#151a22] border border-gray-200 dark:border-gray-700 rounded-xl p-2.5"
                        data-kandang-id="{{ $k->id }}" data-kandang-label="{{ $k->label }}">
                        <div class="flex items-center justify-between text-[10px] font-bold mb-1">
                            <span class="text-gray-600 dark:text-gray-300">{{ $k->label }}</span>
                            <span class="text-gray-400">{{ $k->burungs->count() }} burung</span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-snug truncate" title="{{ $k->burungs->pluck('nama')->implode(', ') }}">
                            {{ $k->burungs->pluck('nama')->implode(', ') ?: 'Kosong' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Riwayat penjualan --}}
    <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Riwayat Penjualan</h3>
        <div class="flex flex-wrap gap-2 items-center">
            <a href="{{ route('admin.baby.index') }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ ! $filter ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">Semua</a>
            <a href="{{ route('admin.baby.index', ['filter' => 'minggu']) }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ $filter === 'minggu' ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">Minggu Ini</a>
            <a href="{{ route('admin.baby.index', ['filter' => '30']) }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ $filter === '30' ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">30 Hari</a>
            <a href="{{ route('admin.baby.index', ['filter' => 'bulan']) }}"
                class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors {{ $filter === 'bulan' ? 'text-white bg-[#E62C37] border-[#E62C37]' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-400' }}">Bulan Ini</a>
            <a href="{{ route('admin.baby.index', ['filter' => 'tahun']) }}"
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
                        <th class="px-6 py-4 w-24">Umur</th>
                        <th class="px-6 py-4 w-32 text-right">Harga</th>
                        <th class="px-6 py-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:border-gray-800 divide-gray-200 dark:divide-gray-800">
                    @forelse ($penjualans as $p)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm tabular-nums whitespace-nowrap">{{ $p->tanggal_terjual->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $p->nama_burung }}</td>
                            <td class="px-6 py-4 text-sm">{{ $p->nomor_ring ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $p->induk_label ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm tabular-nums">{{ $p->usia_hari !== null ? $p->usia_hari.' hari' : '-' }}</td>
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

    {{-- ==================== MODAL EDIT BABY ==================== --}}
    <div id="modalEdit" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Baby <span id="edit_nama_label" class="text-[#E62C37]"></span></h3>
                <button onclick="tutupModal('modalEdit')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form id="formEdit" onsubmit="submitEdit(event)" class="space-y-4" novalidate>
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama</label>
                        <input type="text" name="nama" id="edit_nama"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_nama"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Spesies</label>
                        <input type="text" name="spesies" id="edit_spesies"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_spesies"></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Fase</label>
                        <select name="fase" id="edit_fase"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                            <option value="anakan">Baby</option>
                            <option value="dewasa">Dewasa</option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dewasa = keluar dari menu Baby, masuk pool kandang/aviary.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Gender</label>
                        <select name="gender" id="edit_gender"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                            <option value="">- Belum DNA -</option>
                            <option value="jantan">Jantan</option>
                            <option value="betina">Betina</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">No Ring</label>
                        <input type="text" name="nomor_ring" id="edit_ring"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">No DNA</label>
                        <input type="text" name="nomor_dna" id="edit_dna"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Catatan</label>
                    <textarea name="catatan" id="edit_catatan" rows="2"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalEdit')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnEdit"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
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
                    <select name="burung_id" id="jual_burung" onchange="isiJual()"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                        <option value="">- Pilih burung -</option>
                        @foreach ($burungSemua as $b)
                            <option value="{{ $b->id }}"
                                data-ring="{{ $b->nomor_ring }}" data-induk="{{ $b->induk_label }}" data-usia="{{ $b->usia_hari }}"
                                data-lokasi="{{ $b->kandang?->label }}">{{ $b->nama }} ({{ $b->fase_label }}{{ $b->kandang ? ' · di '.$b->kandang->label : '' }})</option>
                        @endforeach
                    </select>
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
                        <input type="date" name="tanggal_terjual" id="jual_tanggal" max="{{ now('Asia/Jakarta')->toDateString() }}"
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

    {{-- ==================== MODAL PINDAH (tap-to-move) ==================== --}}
    <div id="modalPindah" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pindah Baby</h3>
                <button onclick="tutupModal('modalPindah')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
            <form id="formPindah" onsubmit="submitPindah(event)" class="space-y-4" novalidate>
                @csrf
                <p class="text-sm text-gray-500 dark:text-gray-400">Pindahkan <span id="pindah_nama" class="font-semibold text-gray-900 dark:text-white"></span> ke:</p>
                <div>
                    <select name="kandang_id" id="pindah_target"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] outline-none">
                        <option value="">- Pilih kandang / aviary -</option>
                        @php($targetSemua = $kandangs->flatten()->merge($aviaries))
                        @foreach ($targetSemua as $k)
                            <option value="{{ $k->id }}" data-penuh="{{ $k->burungs->count() >= 2 && $k->tipe !== 'aviary' ? 1 : 0 }}">{{ $k->label }}{{ $k->tipe !== 'aviary' ? ' ('.$k->burungs->count().'/2)' : ' ('.$k->burungs->count().' burung)' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModal('modalPindah')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnPindah"
                        class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                        Pindahkan
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Baby</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Data baby <span id="hapus_nama" class="font-semibold"></span> akan dihapus permanen.</p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="tutupModal('modalHapus')"
                    class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                    Batal
                </button>
                <button type="button" id="btnHapus"
                    class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                    Hapus
                </button>
            </div>
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
        var editTargetId = null;
        var hapusTargetId = null;
        var pindahTargetId = null;

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnEdit, #btnJual, #btnPindah, #btnHapus').forEach(btn => {
                btn.disabled = on;
                btn.classList.toggle('opacity-60', on);
                btn.classList.toggle('cursor-not-allowed', on);
            });
        }

        function bukaModal(id) {
            const m = document.getElementById(id);
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function tutupModal(id) {
            const m = document.getElementById(id);
            m.classList.add('hidden');
            m.classList.remove('flex');
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

        // ==================== EDIT BABY ====================
        function bukaModalEdit(data) {
            editTargetId = data.id;
            bersihkanError(document.getElementById('formEdit'));
            document.getElementById('edit_nama_label').textContent = data.nama;
            document.getElementById('edit_nama').value = data.nama;
            document.getElementById('edit_spesies').value = data.spesies;
            document.getElementById('edit_fase').value = data.fase;
            document.getElementById('edit_gender').value = data.gender || '';
            document.getElementById('edit_ring').value = data.nomor_ring || '';
            document.getElementById('edit_dna').value = data.nomor_dna || '';
            document.getElementById('edit_catatan').value = data.catatan || '';
            bukaModal('modalEdit');
        }

        async function submitEdit(e) {
            e.preventDefault();
            const form = document.getElementById('formEdit');
            bersihkanError(form);
            let valid = true;
            if (!form.nama.value.trim()) { tandaiError('edit_nama', 'err_edit_nama', 'Nama wajib diisi.'); valid = false; }
            if (!form.spesies.value.trim()) { tandaiError('edit_spesies', 'err_edit_spesies', 'Spesies wajib diisi.'); valid = false; }
            if (!valid) return;

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.baby.update', ':id') }}'.replace(':id', editTargetId), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        nama: form.nama.value.trim(),
                        spesies: form.spesies.value.trim(),
                        fase: form.fase.value,
                        gender: form.gender.value || null,
                        nomor_ring: form.nomor_ring.value.trim() || null,
                        nomor_dna: form.nomor_dna.value.trim() || null,
                        catatan: form.catatan.value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) { showToast('error', 'Gagal', data.message || 'Gagal memperbarui data baby.'); return; }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalEdit');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        // ==================== JUAL ====================
        function bukaModalJual(preselectId) {
            const form = document.getElementById('formJual');
            bersihkanError(form);
            form.reset();
            document.getElementById('jual_info').classList.add('hidden');
            document.getElementById('jual_lokasi').classList.add('hidden');
            if (preselectId) {
                form.burung_id.value = preselectId;
                isiJual();
            }
            bukaModal('modalJual');
        }

        function isiJual() {
            const sel = document.getElementById('jual_burung');
            const opt = sel.options[sel.selectedIndex];
            const info = document.getElementById('jual_info');
            if (!opt || !opt.value) { info.classList.add('hidden'); return; }
            info.classList.remove('hidden');
            document.getElementById('jual_ring').textContent = opt.dataset.ring || '-';
            document.getElementById('jual_induk').textContent = opt.dataset.induk || '-';
            document.getElementById('jual_usia').textContent = opt.dataset.usia ? opt.dataset.usia + ' hari' : '-';
            document.getElementById('jual_lokasi').classList.toggle('hidden', !opt.dataset.lokasi);
        }

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

        // ==================== PINDAH (tap-to-move) ====================
        function bukaModalPindah(id, nama) {
            pindahTargetId = id;
            document.getElementById('pindah_nama').textContent = nama;
            document.getElementById('formPindah').reset();
            bukaModal('modalPindah');
        }

        async function submitPindah(e) {
            e.preventDefault();
            const form = document.getElementById('formPindah');
            const sel = document.getElementById('pindah_target');
            if (!form.kandang_id.value) { showToast('error', 'Gagal', 'Pilih kandang / aviary tujuan.'); return; }
            const opt = sel.options[sel.selectedIndex];
            if (opt.dataset.penuh === '1') { showToast('error', 'Gagal', opt.textContent.split(' (')[0] + ' sudah penuh (maksimal 2 burung).'); return; }

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.burung.letakkan', ':id') }}'.replace(':id', pindahTargetId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ kandang_id: Number(form.kandang_id.value) })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) { showToast('error', 'Gagal', data.message || 'Gagal memindahkan baby.'); return; }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalPindah');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        // ==================== HAPUS ====================
        function bukaModalHapus(id, nama) {
            hapusTargetId = id;
            document.getElementById('hapus_nama').textContent = nama;
            bukaModal('modalHapus');
        }

        document.getElementById('btnHapus').addEventListener('click', async function () {
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.baby.destroy', ':id') }}'.replace(':id', hapusTargetId), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) { showToast('error', 'Gagal', data.message || 'Gagal menghapus baby.'); return; }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalHapus');
                window.location.reload();
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        });

        // ==================== FILTER RENTANG ====================
        function terapkanRentang() {
            const dari = document.getElementById('rentang_dari').value;
            const sampai = document.getElementById('rentang_sampai').value;
            if (!dari && !sampai) { showToast('error', 'Gagal', 'Isi minimal satu tanggal.'); return; }
            const url = new URL('{{ route('admin.baby.index') }}');
            url.searchParams.set('filter', 'rentang');
            if (dari) url.searchParams.set('dari', dari);
            if (sampai) url.searchParams.set('sampai', sampai);
            window.location.href = url.toString();
        }

        // ==================== DRAG & DROP baby → kandang/aviary ====================
        document.querySelectorAll('.baby-card').forEach(card => {
            card.addEventListener('dragstart', e => {
                e.dataTransfer.setData('text/plain', String(card.dataset.burungId));
                e.dataTransfer.effectAllowed = 'move';
                card.classList.add('opacity-50');
            });
            card.addEventListener('dragend', () => card.classList.remove('opacity-50'));
        });

        document.querySelectorAll('.target-drop').forEach(target => {
            target.addEventListener('dragover', e => {
                e.preventDefault();
                if (target.dataset.penuh !== '1') target.classList.add('border-[#E62C37]', 'bg-[#E62C37]/5');
            });
            target.addEventListener('dragleave', () => target.classList.remove('border-[#E62C37]', 'bg-[#E62C37]/5'));
            target.addEventListener('drop', async e => {
                e.preventDefault();
                target.classList.remove('border-[#E62C37]', 'bg-[#E62C37]/5');
                if (target.dataset.penuh === '1') { showToast('error', 'Gagal', target.dataset.kandangLabel + ' sudah penuh (maksimal 2 burung).'); return; }
                const id = e.dataTransfer.getData('text/plain');
                if (!id) return;
                try {
                    const res = await fetch('{{ route('admin.burung.letakkan', ':id') }}'.replace(':id', id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify({ kandang_id: Number(target.dataset.kandangId) })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.ok) { showToast('error', 'Gagal', data.message || 'Gagal memindahkan baby.'); return; }
                    showToast('success', 'Berhasil', data.message);
                    window.location.reload();
                } catch (err) {
                    showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
                }
            });
        });
    </script>
@endsection
