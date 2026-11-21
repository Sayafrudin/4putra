@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Baby</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Baby (loloh) umur ≥1 bulan — geser kartu ke kandang/aviary tujuan untuk memindah; penjualan di menu Penjualan Baby</p>
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
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold bg-purple-500/10 text-purple-500 tabular-nums">{{ $b->umur_label ?? '-' }}</span>
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
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1"><span class="font-semibold">Asal kandang:</span> {{ $b->asal_label }}</p>
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
                                {{-- Kandang penuh: diblok gradien merah + tulisan PENUH; detail isi tetap terbaca. --}}
                                <div class="target-drop border rounded-xl p-2.5 {{ $isi >= 2 ? 'border-red-500/60 bg-gradient-to-br from-red-500/20 to-red-500/5 dark:from-red-500/15' : 'bg-white dark:bg-[#151a22] border-gray-200 dark:border-gray-700' }}"
                                    data-kandang-id="{{ $k->id }}" data-kandang-label="{{ $k->label }}"
                                    @if ($isi >= 2) data-penuh="1" @endif>
                                    <div class="flex items-center justify-between text-[10px] font-bold mb-1">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $k->label }}</span>
                                        @if ($isi >= 2)
                                            <span class="text-white bg-red-500/80 px-1.5 py-0.5 rounded">{{ $isi }}/2 PENUH</span>
                                        @else
                                            <span class="text-gray-400">{{ $isi }}/2</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-gray-600 dark:text-gray-300 leading-snug truncate" title="{{ $k->burungs->map(fn ($b) => $b->nama_ring)->implode(', ') }}">
                                        {{ $k->burungs->map(fn ($b) => $b->nama_ring)->implode(', ') ?: 'Kosong' }}
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
                        <p class="text-[11px] text-gray-600 dark:text-gray-300 leading-snug truncate" title="{{ $k->burungs->map(fn ($b) => $b->nama_ring)->implode(', ') }}">
                            {{ $k->burungs->map(fn ($b) => $b->nama_ring)->implode(', ') ?: 'Kosong' }}
                        </p>
                    </div>
                @endforeach
            </div>
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
                    <x-admin.select-search name="kandang_id" id="pindah_target" placeholder="- Pilih kandang / aviary -"
                        :options="($kandangs->flatten()->merge($aviaries))->map(fn ($k) => [
                            'value' => (string) $k->id,
                            'grup' => $k->tipe === 'aviary' ? 'Aviary' : 'Blok '.$k->blok,
                            'label' => $k->label.($k->tipe !== 'aviary' ? ' ('.$k->burungs->count().'/2)' : ' ('.$k->burungs->count().' burung)'),
                            'penuh' => ($k->burungs->count() >= 2 && $k->tipe !== 'aviary') ? 1 : 0,
                            'labelKandang' => $k->label,
                        ])->all()"
                    />
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

    <script>
        var CSRF = document.querySelector('meta[name="csrf-token"]').content;
        var sedangBusy = false;
        var editTargetId = null;
        var hapusTargetId = null;
        var pindahTargetId = null;
        var pindahPenuh = null;
        var pindahLabel = '';

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnEdit, #btnPindah, #btnHapus').forEach(btn => {
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

        // ==================== PINDAH (tap-to-move) ====================
        function bukaModalPindah(id, nama) {
            pindahTargetId = id;
            document.getElementById('pindah_nama').textContent = nama;
            document.getElementById('formPindah').reset();
            window.__ss?.kandang_id?.kosongkan();
            pindahPenuh = null;
            pindahLabel = '';
            bukaModal('modalPindah');
        }

        // Pilihan target (dropdown ber-search) → simpan status penuh + label untuk toast.
        // Hook sinkron dari komponen select-search (bukan event — lihat komponen).
        window.__ssHook = window.__ssHook || {};
        window.__ssHook.kandang_id = (op) => {
            pindahPenuh = Number(op.penuh || 0);
            pindahLabel = op.labelKandang || '';
        };

        async function submitPindah(e) {
            e.preventDefault();
            const form = document.getElementById('formPindah');
            if (!form.kandang_id.value) { showToast('error', 'Gagal', 'Pilih kandang / aviary tujuan.'); return; }
            if (pindahPenuh === 1) { showToast('error', 'Gagal', pindahLabel + ' sudah penuh (maksimal 2 burung).'); return; }

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
                if (target.dataset.penuh !== '1') {
                    // Highlight kuat agar jelas di card kecil: ring penuh + border merah + latar merah tipis
                    target.classList.add('ring-2', 'ring-[#E62C37]', 'border-[#E62C37]', 'bg-[#E62C37]/10');
                    target.classList.remove('border-gray-200', 'dark:border-gray-700');
                }
            });
            const lepasHighlight = () => {
                target.classList.remove('ring-2', 'ring-[#E62C37]', 'border-[#E62C37]', 'bg-[#E62C37]/10');
                target.classList.add('border-gray-200', 'dark:border-gray-700');
            };
            target.addEventListener('dragleave', lepasHighlight);
            target.addEventListener('drop', async e => {
                e.preventDefault();
                lepasHighlight();
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
