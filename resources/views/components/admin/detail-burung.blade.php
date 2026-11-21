@props(['burungs'])

{{-- Modal identitas burung (reusable): dipicu bukaDetailBurung(id). Field opsional
     (gender, ring, DNA, umur, catatan) tampil hanya bila terisi — konvensi identitas. --}}
<div id="modalDetailBurung" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
    <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div class="min-w-0">
                <h3 id="db_nama" class="text-lg font-bold text-gray-900 dark:text-white truncate"></h3>
                <p id="db_spesies" class="text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
            <button type="button" onclick="tutupDetailBurung()"
                class="shrink-0 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <x-tabler-x class="w-5 h-5" />
            </button>
        </div>
        <dl id="db_baris" class="space-y-2 text-sm"></dl>
    </div>
</div>

<script>
    // Turbo menukar body tanpa menjalankan ulang skrip lama — peta & handler
    // ditimpa tiap render (idempoten). Hex-escape: nama/catatan admin tak boleh
    // memutus konteks script.
    @php
        $petaBurung = $burungs->mapWithKeys(fn ($b) => [
            $b->id => [
                'nama' => $b->nama,
                'spesies' => $b->spesies,
                'gender' => $b->gender_label,
                'fase' => $b->fase_label,
                'ring' => $b->nomor_ring,
                'dna' => $b->nomor_dna,
                'umur' => $b->umur_label,
                'lokasi' => $b->kandang?->label,
                'catatan' => $b->catatan,
            ],
        ])->all();
    @endphp
    window.__burungDetail = @json($petaBurung, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

    function tutupDetailBurung() {
        const m = document.getElementById('modalDetailBurung');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function bukaDetailBurung(id) {
        const b = window.__burungDetail[id];
        if (!b) return;
        const m = document.getElementById('modalDetailBurung');
        document.getElementById('db_nama').textContent = b.nama;
        document.getElementById('db_spesies').textContent = b.spesies;
        // Baris identitas via textContent (catatan = input admin, jangan innerHTML)
        const baris = [
            ['Gender', b.gender],
            ['Fase', b.fase],
            ['No Ring', b.ring ? 'Ring ' + b.ring : null],
            ['No DNA', b.dna],
            ['Umur', b.umur],
            ['Lokasi', b.lokasi],
            ['Catatan', b.catatan],
        ];
        const dl = document.getElementById('db_baris');
        dl.innerHTML = '';
        baris
            .filter(([, v]) => v)
            .forEach(([k, v]) => {
                const div = document.createElement('div');
                div.className =
                    'flex justify-between gap-3 border-b border-gray-100 dark:border-gray-800 pb-2 last:border-0 last:pb-0';
                const dt = document.createElement('dt');
                dt.className = 'text-gray-500 dark:text-gray-400 shrink-0';
                dt.textContent = k;
                const dd = document.createElement('dd');
                dd.className = 'font-semibold text-gray-900 dark:text-white text-right';
                dd.textContent = v;
                div.append(dt, dd);
                dl.append(div);
            });
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
</script>
