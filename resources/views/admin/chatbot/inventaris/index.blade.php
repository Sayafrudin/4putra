@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-white uppercase">Inventaris Burung Chatbot</h2>
            <p class="text-sm text-gray-400 mt-1">Kelola data burung yang tersedia untuk chatbot WhatsApp</p>
        </div>
        <div class="flex gap-3">
            <button onclick="bukaModalTambah()"
                class="px-4 py-2 text-sm font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-lg transition-colors">
                + Tambah Inventaris
            </button>
            <a href="{{ route('admin.chatbot.index') }}"
                class="px-4 py-2 text-sm font-bold uppercase tracking-wider text-gray-300 border border-gray-600 hover:border-gray-500 rounded-lg transition-colors">
                Kembali
            </a>
        </div>
    </div>

    {{-- Input search --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" class="table-search-input w-full bg-[#151a22] border border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari inventaris...">
        </div>
    </div>

    {{-- Tabel Inventaris --}}
    <div class="w-full overflow-hidden bg-[#1e2530] border border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-400 bg-[#151a22] border-b border-gray-800">
                        <th class="px-6 py-4">Spesies</th>
                        <th class="px-6 py-4 w-24">Fase</th>
                        <th class="px-6 py-4 w-32">Harga</th>
                        <th class="px-6 py-4 w-20">Stok</th>
                        <th class="px-6 py-4 w-24">Status</th>
                        <th class="px-6 py-4 w-40 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($inventaris as $item)
                        <tr class="text-gray-300 hover:bg-[#262d3a] transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-white">{{ $item->nama_spesies }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $item->fase === 'anakan' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400' }}">
                                    {{ $item->fase === 'anakan' ? 'Baby' : 'Dewasa' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $item->stok }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $item->aktif ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick='bukaModalEdit({{ json_encode($item) }})'
                                        class="px-3 py-1.5 text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors">
                                        Edit
                                    </button>
                                    <button onclick="bukaModalHapus({{ $item->id }}, '{{ $item->nama_spesies }}', '{{ $item->fase === 'anakan' ? 'Baby' : 'Dewasa' }}')"
                                        class="px-3 py-1.5 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg hover:bg-red-500/20 hover:border-red-500/40 transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">Belum ada inventaris.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL TAMBAH ==================== --}}
    <div id="modalTambah" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-[#1e2530] border border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white">Tambah Inventaris Baru</h3>
                <button onclick="tutupModal('modalTambah')" class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formTambah" onsubmit="submitTambah(event)" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Nama Spesies</label>
                    <input type="text" name="nama_spesies" id="tambah_nama" placeholder="Contoh: African Grey"
                        class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_nama"></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Fase</label>
                        <select name="fase" id="tambah_fase"
                            class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="anakan">Baby</option>
                            <option value="dewasa">Dewasa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Stok</label>
                        <input type="number" name="stok" id="tambah_stok" placeholder="0" min="0"
                            class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_stok"></p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Harga (Rp)</label>
                    <input type="number" name="harga" id="tambah_harga" placeholder="0" min="0"
                        class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_harga"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Deskripsi <span class="text-gray-500">(opsional)</span></label>
                    <textarea name="deskripsi" id="tambah_deskripsi" rows="2" placeholder="Deskripsi singkat..."
                        class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none resize-none"></textarea>
                </div>
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" onclick="tutupModal('modalTambah')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-300 bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnTambah"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-[#E62C37] rounded-xl hover:bg-[#c5242d] transition-colors">
                        Tambah
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL EDIT ==================== --}}
    <div id="modalEdit" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-[#1e2530] border border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white">Edit Inventaris</h3>
                <button onclick="tutupModal('modalEdit')" class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formEdit" onsubmit="submitEdit(event)" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Nama Spesies</label>
                    <input type="text" name="nama_spesies" id="edit_nama" placeholder="Nama Spesies"
                        class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_nama"></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Fase</label>
                        <select name="fase" id="edit_fase"
                            class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="anakan">Baby</option>
                            <option value="dewasa">Dewasa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Stok</label>
                        <input type="number" name="stok" id="edit_stok" placeholder="0" min="0"
                            class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_stok"></p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Harga (Rp)</label>
                    <input type="number" name="harga" id="edit_harga" placeholder="0" min="0"
                        class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_harga"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Deskripsi <span class="text-gray-500">(opsional)</span></label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="2" placeholder="Deskripsi singkat..."
                        class="w-full bg-[#151a22] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none resize-none"></textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit_aktif" class="w-4 h-4 rounded border-gray-600 bg-[#151a22] text-[#E62C37] focus:ring-[#E62C37]/50">
                        <span class="text-sm text-gray-300">Aktif</span>
                    </label>
                </div>
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" onclick="tutupModal('modalEdit')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-300 bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnEdit"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL HAPUS ==================== --}}
    <div id="modalHapus" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-[#1e2530] border border-gray-700 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Hapus Inventaris</h3>
                    <p class="text-sm text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            <p class="text-sm text-gray-300 mb-6">Apakah Anda yakin ingin menghapus <span id="hapus_nama" class="font-semibold text-white"></span> (<span id="hapus_fase" class="font-semibold text-white"></span>) dari inventaris?</p>
            <form id="formHapus" onsubmit="submitHapus(event)">
                @csrf
                @method('DELETE')
                <input type="hidden" id="hapus_id">
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="tutupModal('modalHapus')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-300 bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnHapus"
                        class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL SUKSES ==================== --}}
    <div id="modalSukses" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-[#1e2530] border border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-white mb-2" id="sukses_title">Berhasil!</h3>
            <p class="text-sm text-gray-400 mb-6" id="sukses_message">Data berhasil disimpan.</p>
            <button onclick="tutupModal('modalSukses'); location.reload();"
                class="px-5 py-2.5 text-sm font-bold text-white bg-green-600 rounded-xl hover:bg-green-700 transition-colors">
                OK
            </button>
        </div>
    </div>

    <script>
        const STORE_URL = '{{ route("admin.chatbot.inventaris.store") }}';
        const CSRF = '{{ csrf_token() }}';

        // ==================== MODAL HELPERS ====================
        function bukaModal(id) {
            const m = document.getElementById(id);
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function tutupModal(id) {
            const m = document.getElementById(id);
            m.classList.remove('flex');
            m.classList.add('hidden');
            clearErrors();
        }

        function clearErrors() {
            document.querySelectorAll('[id^="err_"]').forEach(el => {
                el.textContent = '';
                el.classList.add('hidden');
            });
        }

        function tampilkanError(fieldId, pesan) {
            const el = document.getElementById('err_' + fieldId);
            if (el) {
                el.textContent = pesan;
                el.classList.remove('hidden');
            }
            const input = document.getElementById(fieldId);
            if (input) input.classList.add('border-red-500');
        }

        // ==================== CUSTOM VALIDATION ====================
        function validateForm(prefix) {
            clearErrors();
            let valid = true;

            const nama = document.getElementById(prefix + '_nama');
            const harga = document.getElementById(prefix + '_harga');
            const stok = document.getElementById(prefix + '_stok');

            if (!nama.value.trim()) {
                tampilkanError(prefix + '_nama', 'Nama spesies wajib diisi');
                valid = false;
            } else if (nama.value.trim().length < 2) {
                tampilkanError(prefix + '_nama', 'Nama spesies minimal 2 karakter');
                valid = false;
            }

            if (!harga.value || parseInt(harga.value) <= 0) {
                tampilkanError(prefix + '_harga', 'Harga harus lebih dari 0');
                valid = false;
            }

            if (!stok.value || parseInt(stok.value) < 0) {
                tampilkanError(prefix + '_stok', 'Stok tidak boleh negatif');
                valid = false;
            }

            return valid;
        }

        // ==================== MODAL TAMBAH ====================
        function bukaModalTambah() {
            document.getElementById('formTambah').reset();
            bukaModal('modalTambah');
        }

        async function submitTambah(e) {
            e.preventDefault();
            if (!validateForm('tambah')) return;

            const btn = document.getElementById('btnTambah');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            try {
                const res = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({
                        nama_spesies: document.getElementById('tambah_nama').value.trim(),
                        fase: document.getElementById('tambah_fase').value,
                        harga: parseInt(document.getElementById('tambah_harga').value),
                        stok: parseInt(document.getElementById('tambah_stok').value),
                        deskripsi: document.getElementById('tambah_deskripsi').value.trim() || null,
                    }),
                });

                const data = await res.json();
                if (res.ok) {
                    tutupModal('modalTambah');
                    document.getElementById('sukses_title').textContent = 'Berhasil Ditambahkan!';
                    document.getElementById('sukses_message').textContent = 'Inventaris baru berhasil ditambahkan ke database.';
                    bukaModal('modalSukses');
                } else {
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([key, msgs]) => {
                            tampilkanError('tambah_' + key.replace('nama_spesies', 'nama'), msgs[0]);
                        });
                    } else {
                        showToast('error', 'Gagal', data.message || 'Gagal menambahkan inventaris');
                    }
                }
            } catch (err) {
                showToast('error', 'Gagal', 'Terjadi kesalahan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Tambah';
            }
        }

        // ==================== MODAL EDIT ====================
        function bukaModalEdit(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_nama').value = item.nama_spesies;
            document.getElementById('edit_fase').value = item.fase;
            document.getElementById('edit_harga').value = item.harga;
            document.getElementById('edit_stok').value = item.stok;
            document.getElementById('edit_deskripsi').value = item.deskripsi || '';
            document.getElementById('edit_aktif').checked = item.aktif;
            bukaModal('modalEdit');
        }

        async function submitEdit(e) {
            e.preventDefault();
            if (!validateForm('edit')) return;

            const id = document.getElementById('edit_id').value;
            const btn = document.getElementById('btnEdit');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            try {
                const res = await fetch(`/admin/chatbot/inventaris/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({
                        nama_spesies: document.getElementById('edit_nama').value.trim(),
                        fase: document.getElementById('edit_fase').value,
                        harga: parseInt(document.getElementById('edit_harga').value),
                        stok: parseInt(document.getElementById('edit_stok').value),
                        deskripsi: document.getElementById('edit_deskripsi').value.trim() || null,
                        aktif: document.getElementById('edit_aktif').checked,
                    }),
                });

                const data = await res.json();
                if (res.ok) {
                    tutupModal('modalEdit');
                    document.getElementById('sukses_title').textContent = 'Berhasil Diperbarui!';
                    document.getElementById('sukses_message').textContent = 'Data inventaris berhasil diperbarui.';
                    bukaModal('modalSukses');
                } else {
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([key, msgs]) => {
                            tampilkanError('edit_' + key.replace('nama_spesies', 'nama'), msgs[0]);
                        });
                    } else {
                        showToast('error', 'Gagal', data.message || 'Gagal memperbarui inventaris');
                    }
                }
            } catch (err) {
                showToast('error', 'Gagal', 'Terjadi kesalahan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Simpan Perubahan';
            }
        }

        // ==================== MODAL HAPUS ====================
        function bukaModalHapus(id, nama, fase) {
            document.getElementById('hapus_id').value = id;
            document.getElementById('hapus_nama').textContent = nama;
            document.getElementById('hapus_fase').textContent = fase;
            bukaModal('modalHapus');
        }

        async function submitHapus(e) {
            e.preventDefault();
            const id = document.getElementById('hapus_id').value;
            const btn = document.getElementById('btnHapus');
            btn.disabled = true;
            btn.textContent = 'Menghapus...';

            try {
                const res = await fetch(`/admin/chatbot/inventaris/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                });

                const data = await res.json();
                if (res.ok) {
                    tutupModal('modalHapus');
                    document.getElementById('sukses_title').textContent = 'Berhasil Dihapus!';
                    document.getElementById('sukses_message').textContent = 'Data inventaris berhasil dihapus dari database.';
                    bukaModal('modalSukses');
                } else {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus inventaris');
                }
            } catch (err) {
                showToast('error', 'Gagal', 'Terjadi kesalahan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Ya, Hapus';
            }
        }
    </script>
@endsection
