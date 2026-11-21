@props([
    'name',
    'options',
    'placeholder' => '- Pilih -',
    'id' => null,
    'selected' => null,
])

{{-- Dropdown ber-search (Alpine): kotak ketik memfilter opsi — untuk daftar panjang
     agar tidak scroll-scroll. Tersembunyi input name dipakai form biasa; setiap
     pemilihan menyebar event "pilih-select" (detail: nama + opsi) untuk reaksi halaman. --}}
<div x-data="selectSearch()"
    @click.outside="terbuka = false"
    class="relative {{ $attributes->only('class') }}"
    data-select-search="{{ $name }}"
    data-options='@json($options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)'>
    <input type="hidden" @if ($id) id="{{ $id }}" @endif name="{{ $name }}" x-ref="hidden"
        value="{{ old($name, $selected) }}">
    <button type="button" @click="terbuka = !terbuka; if (terbuka) $nextTick(() => $refs.cari.focus())"
        class="w-full flex items-center justify-between gap-2 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-left text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
        <span class="truncate" :class="pilih ? '' : 'text-gray-400 dark:placeholder-gray-500'" x-text="pilih ? pilih.label : {{ json_encode($placeholder) }}"></span>
        <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform" :class="terbuka ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </button>
    <div x-show="terbuka" x-transition.opacity.duration.150ms data-panel
        class="absolute z-30 mt-1 w-full bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-xl shadow-2xl overflow-hidden">
        <div class="p-2 border-b border-gray-200 dark:border-gray-800">
            <input type="text" x-ref="cari" x-model="cari" placeholder="Cari..."
                class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none">
        </div>
        <div class="max-h-60 overflow-y-auto py-1">
            <template x-for="op in tersaring" :key="String(op.value)">
                <button type="button" @click="pilihOpsi(op)"
                    class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-[#E62C37]/10 hover:text-[#E62C37] transition-colors"
                    :class="pilih && String(pilih.value) === String(op.value) ? 'bg-[#E62C37]/10 text-[#E62C37] font-semibold' : ''"
                    x-text="op.label"></button>
            </template>
            <p x-show="tersaring.length === 0" class="px-4 py-3 text-sm text-gray-400 italic">Tidak ada hasil.</p>
        </div>
    </div>
</div>

<script>
    // Turbo menukar body tanpa menghidupkan ulang Alpine — alpine:init hanya
    // menyala sekali. Registrasi harus idempoten: langsung bila Alpine sudah
    // hidup, lewat alpine:init bila belum.
    (function () {
        function daftarkan() {
            window.__ss = window.__ss || {};
            window.Alpine.data("selectSearch", () => ({
                terbuka: false,
                cari: "",
                pilih: null,
                daftar: [],
                init() {
                    // nama disimpan di init: saat pilihOpsi dipanggil dari klik opsi,
                    // $el = tombol opsi (bukan root) — dataset tak lagi berisi nama.
                    this.nama = this.$el.dataset.selectSearch;
                    this.daftar = JSON.parse(this.$el.dataset.options || "[]");
                    window.__ss[this.$el.dataset.selectSearch] = this;
                    const semula = this.$el.querySelector("[type=hidden]").value;
                    if (semula !== "") this.set(semula);
                },
                get tersaring() {
                    const q = this.cari.toLowerCase().trim();
                    if (!q) return this.daftar;
                    return this.daftar.filter((op) =>
                        op.label.toLowerCase().includes(q),
                    );
                },
                pilihOpsi(op) {
                    this.pilih = op;
                    this.$refs.hidden.value = op.value;
                    this.terbuka = false;
                    this.cari = "";
                    const nama = this.nama;
                    document.dispatchEvent(
                        new CustomEvent("pilih-select", {
                            bubbles: true,
                            detail: { nama: nama, opsi: op },
                        }),
                    );
                    // Hook sinkron: jalur yang dijamin berjalan (event dari dalam
                    // handler Alpine ternyata tidak selalu menjangkau listener).
                    const hook = (window.__ssHook || {})[nama];
                    if (typeof hook === "function") hook(op);
                },
                set(nilai) {
                    const op = this.daftar.find(
                        (o) => String(o.value) === String(nilai),
                    );
                    if (op) this.pilihOpsi(op);
                },
                kosongkan() {
                    this.pilih = null;
                    this.cari = "";
                    this.$refs.hidden.value = "";
                },
            }));
        }
        if (window.Alpine) {
            daftarkan();
        } else {
            document.addEventListener("alpine:init", daftarkan, {
                once: true,
            });
        }
    })();
</script>
