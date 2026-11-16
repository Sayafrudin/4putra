@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Kandang & Penempatan Burung</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Geser kartu burung ke kandang kosong ({{ $terpasang }}/{{ $total }} burung terpasang)</p>
        </div>
        <div class="flex gap-3">
            <button onclick="bukaModalTambah()"
                class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-xl transition-colors">
                + Tambah Burung
            </button>
        </div>
    </div>

    {{-- Pool: burung belum ditempatkan (zona drop juga) --}}
    <div class="mb-8 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Belum Ditempatkan</h3>
            <span data-pool-count class="text-xs font-bold text-[#E62C37] bg-[#E62C37]/10 px-2.5 py-1 rounded-full">{{ $pool->count() }} burung</span>
        </div>
        <div id="poolList" class="p-4 min-h-20 flex flex-wrap gap-2"
            data-drop-pool>
            @forelse($pool as $b)
                <x-admin.burung-chip :burung="$b" />
            @empty
                <p class="text-sm text-gray-400 dark:text-gray-500 px-1 py-2">Semua burung sudah ditempatkan di kandang.</p>
            @endforelse
        </div>
    </div>

    {{-- Papan kandang per blok --}}
    <div class="space-y-8 pb-16">
        @foreach ($kandangs as $blok => $kandangBlok)
            <section>
                <div class="flex items-center gap-3 mb-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Blok {{ $blok }}</h3>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-full">{{ ucfirst($kandangBlok->first()->tipe) }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $kandangBlok->count() }} kandang</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    @forelse ($kandangBlok ?? collect() as $k)
                        <div class="kandang-drop relative bg-white dark:bg-[#151a22] border border-gray-200 dark:border-gray-700 rounded-xl p-2.5 min-h-24 flex flex-col"
                            data-kandang-id="{{ $k->id }}"
                            data-kandang-label="{{ $k->label }}">
                            <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider mb-1.5">
                                <span class="text-gray-600 dark:text-gray-300">{{ $k->label }}</span>
                                <span class="kandang-status {{ $k->burungs->isEmpty() ? 'text-gray-400 dark:text-gray-500' : 'text-[#E62C37]' }}">{{ $k->burungs->isEmpty() ? 'Kosong' : $k->burungs->count().' burung' }}</span>
                            </div>
                            <div class="kandang-isi flex flex-col gap-1.5 grow">
                                @foreach ($k->burungs as $b)
                                    <x-admin.burung-chip :burung="$b" />
                                @endforeach
                            </div>
                            @if ($k->burungs->isEmpty())
                                <span class="kosong-hint text-[10px] text-gray-300 dark:text-gray-600 text-center py-1.5 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">Kosong — tarik burung ke sini</span>
                            @endif
                        </div>
                    @empty
                        <p class="col-span-full text-sm text-gray-400">Belum ada kandang di blok ini.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    {{-- ==================== MODAL TAMBAH BURUNG ==================== --}}
    <div id="modalTambah" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tambah Burung Baru</h3>
                <button onclick="tutupModal('modalTambah')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formTambah" onsubmit="submitTambah(event)" class="space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama / Panggilan</label>
                    <input type="text" name="nama" id="tambah_nama" placeholder="Contoh: Camelot"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_nama"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Spesies</label>
                    <input type="text" name="spesies" id="tambah_spesies" placeholder="Contoh: African Grey"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_spesies"></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Fase</label>
                        <select name="fase" id="tambah_fase"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="anakan">Baby</option>
                            <option value="dewasa">Dewasa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Gender (opsional)</label>
                        <select name="gender" id="tambah_gender"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                            <option value="">Belum ada data DNA</option>
                            <option value="jantan">Jantan</option>
                            <option value="betina">Betina</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Catatan (opsional)</label>
                    <textarea name="catatan" id="tambah_catatan" rows="2" placeholder="Kondisi khusus burung..."
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

    {{-- ==================== MODAL HAPUS BURUNG ==================== --}}
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Burung</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hapus <span id="hapus_nama" class="font-semibold text-gray-700 dark:text-gray-200"></span> secara permanen?</p>
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

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        let sedangBusy = false;
        let burungDitarik = null; // { id, chip, dariParent }
        let hapusTargetId = null;

        function setBusy(on) {
            sedangBusy = on;
            document.querySelectorAll('#btnTambah, #btnHapus').forEach(btn => btn.disabled = on);
            if (on) document.querySelectorAll('#btnTambah, #btnHapus').forEach(btn => btn.classList.add('opacity-60', 'cursor-not-allowed'));
            else document.querySelectorAll('#btnTambah, #btnHapus').forEach(btn => btn.classList.remove('opacity-60', 'cursor-not-allowed'));
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

        function bukaModalTambah() { bukaModal('modalTambah'); }

        // ---- Validasi kustom (tanpa HTML5 default) ----
        function tandaiError(inputId, errId, pesan) {
            const input = document.getElementById(inputId);
            const err = document.getElementById(errId);
            input.classList.add('border-red-400');
            err.textContent = pesan;
            err.classList.remove('hidden');
        }

        function bersihkanError(form) {
            form.querySelectorAll('input, select, textarea').forEach(el => el.classList.remove('border-red-400'));
            form.querySelectorAll('.text-red-400').forEach(p => p.classList.add('hidden'));
        }

        async function submitTambah(e) {
            e.preventDefault();
            const form = document.getElementById('formTambah');
            bersihkanError(form);

            const nama = form.nama.value.trim();
            const spesies = form.spesies.value.trim();
            let valid = true;
            if (!nama) { tandaiError('tambah_nama', 'err_tambah_nama', 'Nama burung wajib diisi.'); valid = false; }
            if (!spesies) { tandaiError('tambah_spesies', 'err_tambah_spesies', 'Spesies wajib diisi.'); valid = false; }
            if (!valid) return;

            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.burung.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        nama: nama,
                        spesies: spesies,
                        fase: form.fase.value,
                        gender: form.gender.value || null,
                        catatan: form.catatan.value.trim() || null,
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menyimpan burung.');
                    return;
                }
                showToast('success', 'Berhasil', data.message);
                tutupModal('modalTambah');
                window.location.reload(); // chip baru dirender server-side (aman dari injeksi)
            } catch (err) {
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                setBusy(false);
            }
        }

        // ---- Drag & drop (HTML5, event delegation) ----
        document.addEventListener('dragstart', function (e) {
            const chip = e.target.closest('.burung-chip');
            if (!chip || sedangBusy) { e.preventDefault(); return; }
            burungDitarik = { id: chip.dataset.burungId, chip: chip, dariParent: chip.parentElement };
            chip.classList.add('opacity-50');
            e.dataTransfer.effectAllowed = 'move';
        });

        document.addEventListener('dragend', function () {
            if (burungDitarik) burungDitarik.chip.classList.remove('opacity-50');
            document.querySelectorAll('.kandang-drop').forEach(z => z.classList.remove('ring-2', 'ring-[#E62C37]/50'));
            document.getElementById('poolList').classList.remove('ring-2', 'ring-[#E62C37]/50');
        });

        document.addEventListener('dragover', function (e) {
            const zona = e.target.closest('.kandang-drop, [data-drop-pool]');
            if (zona && burungDitarik && !sedangBusy) {
                e.preventDefault();
                zona.classList.add('ring-2', 'ring-[#E62C37]/50');
            }
        });

        document.addEventListener('dragleave', function (e) {
            const zona = e.target.closest('.kandang-drop, [data-drop-pool]');
            if (zona) zona.classList.remove('ring-2', 'ring-[#E62C37]/50');
        });

        document.addEventListener('drop', async function (e) {
            const zona = e.target.closest('.kandang-drop, [data-drop-pool]');
            if (!zona || !burungDitarik || sedangBusy) return;
            e.preventDefault();
            zona.classList.remove('ring-2', 'ring-[#E62C37]/50');

            const { id, chip, dariParent } = burungDitarik;
            const kePool = zona.hasAttribute('data-drop-pool');
            const asalCage = chip.closest('.kandang-drop');
            if (kePool && !asalCage) return; // sudah di pool
            if (!kePool && asalCage === zona) return; // kandang sama

            // Optimistic: pindahkan chip, revert jika server menolak
            const isi = kePool ? zona : zona.querySelector('.kandang-isi');
            const kosongHint = zona.querySelector('.kosong-hint');
            if (kosongHint) kosongHint.classList.add('hidden');
            const anchor = isi.querySelector('.burung-chip');
            isi.insertBefore(chip, anchor);

            setBusy(true);
            try {
                const url = kePool ? '{{ route('admin.burung.kosongkan', ':id') }}'.replace(':id', id)
                    : '{{ route('admin.burung.letakkan', ':id') }}'.replace(':id', id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify(kePool ? {} : { kandang_id: Number(zona.dataset.kandangId) })
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    dariParent.insertBefore(chip, dariParent.querySelector('.burung-chip'));
                    showToast('error', 'Gagal', data.message || 'Gagal memindahkan burung.');
                    return;
                }
                perbaruiStatusKandang(asalCage);
                perbaruiStatusKandang(kePool ? null : zona);
                if (kePool) perbaruiStatusKandang(null);
                showToast('success', 'Berhasil', data.message);
            } catch (err) {
                dariParent.insertBefore(chip, dariParent.querySelector('.burung-chip'));
                showToast('error', 'Gagal', 'Tidak dapat terhubung ke server.');
            } finally {
                burungDitarik = null;
                setBusy(false);
            }
        });

        function perbaruiStatusKandang(zona) {
            const pool = document.getElementById('poolList');
            const chips = pool.querySelectorAll('.burung-chip').length;
            document.querySelector('[data-pool-count]').textContent = chips + ' burung';

            if (!zona) return;
            const isi = zona.querySelectorAll('.kandang-isi .burung-chip').length;
            const status = zona.querySelector('.kandang-status');
            const hint = zona.querySelector('.kosong-hint');
            if (isi === 0) {
                status.textContent = 'Kosong';
                status.classList.remove('text-[#E62C37]');
                status.classList.add('text-gray-400', 'dark:text-gray-500');
                if (!hint) {
                    const span = document.createElement('span');
                    span.className = 'kosong-hint text-[10px] text-gray-300 dark:text-gray-600 text-center py-1.5 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg';
                    span.textContent = 'Kosong — tarik burung ke sini';
                    zona.querySelector('.kandang-isi').after(span);
                } else {
                    hint.classList.remove('hidden');
                }
            } else {
                status.textContent = isi + ' burung';
                status.classList.add('text-[#E62C37]');
                status.classList.remove('text-gray-400', 'dark:text-gray-500');
                if (hint) hint.classList.add('hidden');
            }
        }

        // ---- Hapus burung (wajib modal konfirmasi) ----
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.hapus-burung');
            if (!btn || sedangBusy) return;
            hapusTargetId = btn.dataset.id;
            document.getElementById('hapus_nama').textContent = btn.dataset.nama;
            bukaModal('modalHapus');
        });

        document.getElementById('btnHapus').addEventListener('click', async function () {
            if (!hapusTargetId) return;
            setBusy(true);
            try {
                const res = await fetch('{{ route('admin.burung.destroy', ':id') }}'.replace(':id', hapusTargetId), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus burung.');
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
