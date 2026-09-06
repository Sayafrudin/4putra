@extends('layouts.admin')

@section('content')
    <div
        class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">
                Monitoring Data Achievements
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Manajemen portofolio berkas publikasi penangkaran PT 4Putra Vertex Aviary secara real-time
            </p>
        </div>

        <button onclick="openCreateModal()"
            class="action-btn px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] transition-colors duration-200 shadow-md focus:outline-none">
            + Tambah Portofolio
        </button>
    </div>

    {{-- Input search --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" class="table-search-input w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari portofolio...">
        </div>
    </div>

    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full min-w-[640px] text-left border-collapse">
                <thead>
                    <tr
                        class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-4 sm:px-6 py-4 w-[50%]">Informasi Pencapaian / Judul</th>
                        <th class="px-4 sm:px-6 py-4 w-20 sm:w-28">Tahun</th>
                        <th class="px-4 sm:px-6 py-4 w-28 sm:w-36">Tanggal Input</th>
                        <th class="px-4 sm:px-6 py-4 w-28 sm:w-40 text-right">Tindakan Kontrol</th>
                    </tr>
                </thead>
                <tbody data-admin-list class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-600 dark:text-gray-300">
                    @forelse($achievements as $achievement)
                        <tr class="hover:bg-gray-800/50 transition-colors duration-150">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="flex items-center gap-3 sm:gap-4">
                                    @if ($achievement->images->count() > 0)
                                        <div class="flex flex-wrap gap-1.5 max-w-[180px] shrink-0">
                                            @foreach ($achievement->images as $image)
                                                @php
                                                    $achImg = str_starts_with($image->image_path, 'http')
                                                        ? str_replace('/upload/', '/upload/w_100,h_100,c_fill,q_auto,f_auto/', $image->image_path)
                                                        : asset('storage/achievements/' . $image->image_path);
                                                @endphp
                                                <img src="{{ $achImg }}"
                                                    onclick="zoomImage(this.src)"
                                                    class="w-11 h-11 rounded border border-gray-300 dark:border-gray-700 object-cover cursor-pointer hover:scale-125 hover:z-10 hover:border-[#E62C37] shadow-sm transition-all duration-200 relative"
                                                    alt="Foto {{ $achievement->title }}" title="Klik untuk preview"
                                                    loading="lazy">
                                            @endforeach
                                        </div>
                                    @else
                                        <div
                                            class="w-11 h-11 rounded bg-gray-800 border border-gray-300 dark:border-gray-700 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                                stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-gray-900 dark:text-white uppercase text-sm truncate">
                                            {{ $achievement->title }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[120px] sm:max-w-sm mt-0.5">
                                            {{ Str::limit($achievement->description, 50) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                <span class="px-2.5 py-1 text-xs font-bold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151a22] rounded whitespace-nowrap">
                                    {{ $achievement->year }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($achievement->date)->format('d M Y') }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2 whitespace-nowrap">
                                    <button onclick="openEditModal({{ json_encode($achievement, JSON_BIGINT_AS_STRING) }})"
                                        class="action-btn px-3 py-1.5 text-xs font-bold uppercase tracking-wider border-2 border-amber-500 text-amber-500 hover:bg-amber-500 hover:text-white transition-all duration-200 focus:outline-none">Ubah</button>
                                    <button
                                        onclick="openDeleteModal({{ $achievement->id }}, '{{ addslashes($achievement->title) }}')"
                                        class="action-btn px-3 py-1.5 text-xs font-bold uppercase tracking-wider border-2 border-[#E62C37] text-[#E62C37] hover:bg-[#E62C37] hover:text-white transition-all duration-200 focus:outline-none">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 sm:px-6 py-12 text-sm text-center text-gray-500 bg-white dark:bg-[#151a22]">
                                Sistem mendeteksi belum ada berkas pencapaian yang tersimpan di dalam database
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===================== SEKSI 2: MANAJEMEN ABOUT US ===================== --}}
    <div class="my-10 flex items-center gap-4">
        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-800"></div>
        <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white uppercase whitespace-nowrap">
            Manajemen About Us
        </h2>
        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-800"></div>
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400 -mt-6 mb-6 text-center">
        Kelola media hero & tim leadership halaman publik About Us
    </p>

    {{-- ===== Sub-seksi: Media Hero ===== --}}
    <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold uppercase tracking-wide text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 bg-[#E62C37]"></span> Media Hero
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Foto/video utama About Us (upload Cloudinary; video otomatis autoplay & tanpa download)
            </p>
        </div>
        <button onclick="openMediaModal()"
            class="action-btn px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] transition-colors duration-200 shadow-md focus:outline-none">
            Ubah Media
        </button>
    </div>

    <div class="w-full bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 p-5 flex flex-col sm:flex-row items-start gap-5">
        @if ($aboutPage->media_type === 'video')
            <video src="{{ $aboutPage->mediaUrl() }}" muted controls preload="metadata"
                class="max-h-56 rounded-lg border border-gray-300 dark:border-gray-700"></video>
        @else
            <img src="{{ $aboutPage->mediaUrl() }}" alt="Media hero About Us"
                class="h-40 w-32 object-cover rounded-lg border border-gray-300 dark:border-gray-700"
                onclick="zoomImage(this.src)">
        @endif
        <div>
            <span class="inline-block px-2.5 py-1 text-xs font-bold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151a22] rounded uppercase">
                Tipe saat ini: {{ $aboutPage->media_type === 'video' ? 'Video' : 'Foto' }}
            </span>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 break-all max-w-xl">
                {{ $aboutPage->media_path }}
            </p>
        </div>
    </div>

    {{-- ===== Sub-seksi: Leadership ===== --}}
    <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold uppercase tracking-wide text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 bg-[#E62C37]"></span> Leadership
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Tim manajemen di bagian "Meet Our Leadership" (nama, role, foto, urutan)
            </p>
        </div>
        <button onclick="openLeaderModal()"
            class="action-btn px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] transition-colors duration-200 shadow-md focus:outline-none">
            + Tambah Management
        </button>
    </div>

    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10">
        <div class="w-full overflow-x-auto">
            <table class="w-full min-w-[640px] text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-4 sm:px-6 py-4">Foto</th>
                        <th class="px-4 sm:px-6 py-4">Nama</th>
                        <th class="px-4 sm:px-6 py-4">Role (ID)</th>
                        <th class="px-4 sm:px-6 py-4">Role (EN)</th>
                        <th class="px-4 sm:px-6 py-4">Urutan</th>
                        <th class="px-4 sm:px-6 py-4 text-right">Tindakan Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-600 dark:text-gray-300">
                    @forelse($leaderships as $leader)
                        <tr class="hover:bg-gray-800/50 transition-colors duration-150">
                            <td class="px-4 sm:px-6 py-4">
                                <img src="{{ $leader->photoUrl() }}" alt="{{ $leader->name }}"
                                    onclick="zoomImage(this.src)"
                                    class="w-12 h-12 rounded-lg border border-gray-300 dark:border-gray-700 object-cover cursor-pointer hover:scale-125 hover:z-10 hover:border-[#E62C37] shadow-sm transition-all duration-200 relative"
                                    loading="lazy">
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                {{ $leader->name }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm">{{ $leader->role }}</td>
                            <td class="px-4 sm:px-6 py-4 text-sm">{{ $leader->role_en ?? '-' }}</td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151a22] rounded">
                                    {{ $leader->sort_order }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2 whitespace-nowrap">
                                    <button onclick='openLeaderModal({{ json_encode($leader, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})'
                                        class="action-btn px-3 py-1.5 text-xs font-bold uppercase tracking-wider border-2 border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white transition-all duration-200 focus:outline-none">Ubah</button>
                                    <button onclick="openLeaderDeleteModal({{ $leader->id }}, '{{ addslashes($leader->name) }}')"
                                        class="action-btn px-3 py-1.5 text-xs font-bold uppercase tracking-wider border-2 border-[#E62C37] text-[#E62C37] hover:bg-[#E62C37] hover:text-white transition-all duration-200 focus:outline-none">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 sm:px-6 py-12 text-sm text-center text-gray-500 bg-white dark:bg-[#151a22]">
                                Belum ada data leadership
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===================== MODAL CREATE ===================== --}}
    <x-admin.modal id="create" maxWidth="max-w-2xl">
        <x-admin.modal-close id="create" />
        <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-200 dark:border-gray-800 pr-12">
            <h3 class="text-lg font-bold uppercase tracking-wide text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 bg-[#E62C37]"></span> Formulir Input Portofolio Baru
            </h3>
        </div>

        <form id="form-create-achievements" action="{{ route('admin.achievements.store') }}" method="POST"
            enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Judul
                    Portofolio (ID)</label>
                <input type="text" id="create-input-title" name="title"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                <span id="error-create-title" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Kolom judul wajib
                    diisi dengan benar</span>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                    Teks Merah pada Judul (ID)
                    <span class="font-normal normal-case text-gray-500">(opsional, misal: nama event/tempat)</span>
                </label>
                <input type="text" name="title_highlight" placeholder="Contoh: Bupati Cup 2025"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                    Teks Merah pada Judul (EN)
                    <span class="font-normal normal-case text-gray-500">(opsional, versi bahasa Inggris)</span>
                </label>
                <input type="text" name="title_highlight_en" placeholder="Example: Bupati Cup 2025"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Judul Portofolio
                    Terjemahan (EN)</label>
                <input type="text" name="title_en"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Tahun
                        Event</label>
                    <input type="number" id="create-input-year" name="year" min="2000" max="2099"
                        class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                    <span id="error-create-year" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Tahun event
                        wajib diisi</span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Tanggal Pelaksanaan</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <input type="date" id="create-input-date" name="date" placeholder="Mulai"
                                class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37] cursor-pointer dark:[color-scheme:dark]">
                            <span class="text-[10px] text-gray-500 mt-0.5 block">Tanggal Mulai</span>
                        </div>
                        <div>
                            <input type="date" id="create-input-date-end" name="date_end" placeholder="Selesai"
                                class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37] cursor-pointer dark:[color-scheme:dark]">
                            <span class="text-[10px] text-gray-500 mt-0.5 block">Tanggal Selesai (opsional)</span>
                        </div>
                    </div>
                    <span id="error-create-date" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Tanggal
                        pelaksanaan wajib dipilih</span>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Lokasi / Kota</label>
                <input type="text" id="create-input-location" name="location" placeholder="Contoh: Surabaya, Jakarta, Bali"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                <span class="text-[10px] text-gray-500 mt-0.5 block">Kosongkan jika di Surabaya</span>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Deskripsi
                    Lengkap</label>
                <textarea id="create-input-description" name="description" rows="4"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]"></textarea>
                <span id="error-create-description" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Deskripsi
                    portofolio tidak boleh kosong</span>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Deskripsi Lengkap
                    Terjemahan (EN)</label>
                <textarea name="description_en" rows="4"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Link Video
                    <span class="font-normal normal-case text-gray-500">(opsional, bisa YouTube/GDrive/Vimeo/dll)</span>
                </label>
                <div id="create-video-url-list" class="space-y-2">
                    <div class="flex items-center gap-2">
                        <input type="url" name="video_url[]" placeholder="https://youtube.com/watch?v=... atau https://drive.google.com/..."
                            class="flex-1 p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                        <button type="button" onclick="tambahLinkItem('create-video-url-list')" class="px-3 py-2.5 text-sm font-bold bg-green-600/20 text-green-400 border border-green-500/30 rounded-xl hover:bg-green-600/30 transition-colors">+</button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Link Eksternal
                    <span class="font-normal normal-case text-gray-500">(opsional, link berita/artikel terkait, bisa lebih dari 1)</span>
                </label>
                <div id="create-external-link-list" class="space-y-2">
                    <div class="flex items-center gap-2">
                        <input type="text" name="external_label[]" placeholder="Nama headline (opsional)"
                            class="w-2/5 p-2.5 text-xs bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                        <input type="url" name="external_link[]" placeholder="https://berita.com/artikel..."
                            class="flex-1 p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                        <button type="button" onclick="tambahLinkItem('create-external-link-list')" class="px-3 py-2.5 text-sm font-bold bg-green-600/20 text-green-400 border border-green-500/30 rounded-xl hover:bg-green-600/30 transition-colors">+</button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Upload Dokumentasi
                    <span class="font-normal normal-case text-gray-500">(foto & video bisa diunggah sekaligus)</span>
                </label>
                <div id="dropzone-create"
                    class="dropzone !bg-white dark:bg-[#151a22] border-2 border-dashed border-gray-300 dark:border-gray-700 rounded p-6 text-center cursor-pointer hover:border-[#E62C37] transition-colors min-h-[140px]">
                    <div class="dz-message text-sm text-gray-500 dark:text-gray-400">
                        Tarik file foto/video ke sini atau klik untuk memilih
                    </div>
                </div>
                <span id="error-create-photos" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Minimal unggah
                    satu foto dokumentasi</span>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeModal('create')"
                    class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
                <button type="button" id="submit-create-btn"
                    class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] transition-colors rounded-xl">Simpan
                    Data</button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ===================== MODAL EDIT ===================== --}}
    <x-admin.modal id="edit" maxWidth="max-w-2xl">
        <x-admin.modal-close id="edit" color="hover:bg-amber-500" />

        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-gray-800 pr-12">
            <h3 class="text-lg font-bold uppercase tracking-wide text-gray-900 dark:text-white flex items-center gap-2">
                <span
                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-amber-950 text-amber-500 font-black text-sm">!</span>
                Modifikasi Portofolio Terpilih
            </h3>
        </div>

        <form id="form-edit-action" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Judul
                    Portofolio (ID)</label>
                <input type="text" name="title" id="edit-title"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-amber-500">
                <span id="error-edit-title" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Kolom judul wajib
                    diisi</span>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                    Teks Merah pada Judul (ID)
                    <span class="font-normal normal-case text-gray-500">(opsional, misal: nama event/tempat)</span>
                </label>
                <input type="text" name="title_highlight" id="edit-title-highlight"
                    placeholder="Contoh: Bupati Cup 2025"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                    Teks Merah pada Judul (EN)
                    <span class="font-normal normal-case text-gray-500">(opsional, versi bahasa Inggris)</span>
                </label>
                <input type="text" name="title_highlight_en" id="edit-title-highlight-en"
                    placeholder="Example: Bupati Cup 2025"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Judul Portofolio
                    Terjemahan (EN)</label>
                <input type="text" name="title_en" id="edit-title-en"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Tahun
                        Event</label>
                    <input type="number" name="year" id="edit-year"
                        class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <span id="error-edit-year" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Tahun event
                        wajib diisi</span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Tanggal Pelaksanaan</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <input type="date" name="date" id="edit-date" placeholder="Mulai"
                                class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-amber-500 cursor-pointer dark:[color-scheme:dark]">
                            <span class="text-[10px] text-gray-500 mt-0.5 block">Tanggal Mulai</span>
                        </div>
                        <div>
                            <input type="date" name="date_end" id="edit-date-end" placeholder="Selesai"
                                class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-amber-500 cursor-pointer dark:[color-scheme:dark]">
                            <span class="text-[10px] text-gray-500 mt-0.5 block">Tanggal Selesai (opsional)</span>
                        </div>
                    </div>
                    <span id="error-edit-date" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Tanggal
                        pelaksanaan wajib dipilih</span>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Lokasi / Kota</label>
                <input type="text" name="location" id="edit-location" placeholder="Contoh: Surabaya, Jakarta, Bali"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-amber-500">
                <span class="text-[10px] text-gray-500 mt-0.5 block">Kosongkan jika di Surabaya</span>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Deskripsi
                    Lengkap (ID)</label>
                <textarea name="description" id="edit-description" rows="4"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                <span id="error-edit-description" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Deskripsi
                    tidak boleh kosong</span>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Deskripsi Lengkap
                    Terjemahan (EN)</label>
                <textarea name="description_en" id="edit-description-en" rows="4"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Link Video
                    <span class="font-normal normal-case text-gray-500">(opsional, bisa YouTube/GDrive/Vimeo/dll)</span>
                </label>
                <div id="edit-video-url-list" class="space-y-2">
                    {{-- diisi dinamis oleh JS --}}
                </div>
                <button type="button" onclick="tambahLinkItem('edit-video-url-list')" class="mt-2 px-3 py-1.5 text-xs font-bold bg-green-600/20 text-green-400 border border-green-500/30 rounded-lg hover:bg-green-600/30 transition-colors">+ Tambah Link Video</button>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Link Eksternal
                    <span class="font-normal normal-case text-gray-500">(opsional, nama headline diisi manual / otomatis dari judul halaman, kosongkan untuk ambil otomatis)</span>
                </label>
                <div id="edit-external-link-list" class="space-y-2">
                    {{-- diisi dinamis oleh JS --}}
                </div>
                <button type="button" onclick="tambahLinkItem('edit-external-link-list')" class="mt-2 px-3 py-1.5 text-xs font-bold bg-green-600/20 text-green-400 border border-green-500/30 rounded-lg hover:bg-green-600/30 transition-colors">+ Tambah Link Eksternal</button>
            </div>

            {{-- Video tersimpan --}}
            <div id="edit-existing-video-wrap" class="hidden">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Video Tersimpan</label>
                <div id="edit-existing-video"
                    class="flex items-center gap-3 p-3 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded">
                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <span id="edit-video-name" class="text-xs text-gray-600 dark:text-gray-300 truncate flex-1"></span>
                    <button type="button" id="btn-delete-video"
                        class="px-2 py-1 text-xs font-bold uppercase tracking-wider text-[#E62C37] border border-[#E62C37] hover:bg-[#E62C37] hover:text-white transition-colors rounded">
                        Hapus
                    </button>
                </div>
            </div>

            {{-- GALERI FOTO TERSIMPAN + HAPUS PER-FOTO --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                    Foto Tersimpan Saat Ini
                </label>
                <div id="edit-existing-photos"
                    class="flex flex-wrap gap-2 p-3 bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded min-h-[70px]">
                    {{-- diisi dinamis oleh achievements.js --}}
                </div>
                <p class="text-[11px] text-gray-500 mt-1">Klik ikon &times; pada foto untuk menghapusnya secara
                    permanen.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Tambah Foto/Video
                    Dokumentasi Baru</label>
                <div id="dropzone-edit"
                    class="dropzone !bg-white dark:bg-[#151a22] border-2 border-dashed border-gray-300 dark:border-gray-700 rounded p-6 text-center cursor-pointer hover:border-amber-500 transition-colors min-h-[140px]">
                    <div class="dz-message text-sm text-gray-500 dark:text-gray-400">
                        Tarik file foto/video baru ke sini untuk menambah koleksi
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeModal('edit')"
                    class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
                <button type="button" id="submit-edit-btn"
                    class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-amber-500 hover:bg-amber-600 transition-colors rounded-xl">Perbarui
                    Data</button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ===================== MODAL KONFIRMASI UPDATE ===================== --}}
    <x-admin.modal id="confirm-update" maxWidth="max-w-md" zIndex="z-[60]">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Konfirmasi Pembaruan</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pastikan data sudah benar</p>
            </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">Lanjutkan pembaruan data portofolio?</p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeModal('confirm-update')"
                class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Periksa
                Lagi</button>
            <button type="button" id="confirm-update-proceed-btn"
                class="px-5 py-2.5 text-sm font-bold text-white bg-amber-500 rounded-xl hover:bg-amber-600 transition-colors">Ya,
                Perbarui</button>
        </div>
    </x-admin.modal>

    {{-- ===================== MODAL DELETE ===================== --}}
    <x-admin.modal-confirm-delete id="delete" formId="form-delete-action" title="Konfirmasi Penghapusan">
        Apakah Anda yakin menghapus portofolio <span id="delete-target-name" class="font-bold text-gray-900 dark:text-white"></span>
    </x-admin.modal-confirm-delete>

    {{-- ===================== MODAL KONFIRMASI HAPUS FOTO ===================== --}}
    <x-admin.modal id="confirm-delete-photo" maxWidth="max-w-md" zIndex="z-[60]">
        <x-admin.modal-close id="confirm-delete-photo" />
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Foto</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
            </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">Apakah Anda yakin menghapus foto ini secara permanen?</p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeModal('confirm-delete-photo')"
                class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
            <button type="button" id="confirm-delete-photo-btn"
                class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">Ya,
                Hapus Foto</button>
        </div>
    </x-admin.modal>

    {{-- ===================== MODAL KONFIRMASI HAPUS VIDEO ===================== --}}
    <x-admin.modal id="confirm-delete-video" maxWidth="max-w-md" zIndex="z-[60]">
        <x-admin.modal-close id="confirm-delete-video" />
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Video</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
            </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">Apakah Anda yakin menghapus video yang tersimpan?</p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeModal('confirm-delete-video')"
                class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
            <button type="button" id="confirm-delete-video-btn"
                class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">Ya,
                Hapus Video</button>
        </div>
    </x-admin.modal>

    {{-- ===================== MODAL UBAH MEDIA ABOUT ===================== --}}
    <x-admin.modal id="about-media" maxWidth="max-w-xl">
        <x-admin.modal-close id="about-media" />
        <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-200 dark:border-gray-800 pr-12">
            <h3 class="text-lg font-bold uppercase tracking-wide text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 bg-[#E62C37]"></span> Ubah Media Hero About Us
            </h3>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Media Saat Ini</label>
                @if ($aboutPage->media_type === 'video')
                    <video src="{{ $aboutPage->mediaUrl() }}" muted controls preload="metadata"
                        class="max-h-40 rounded-lg border border-gray-300 dark:border-gray-700"></video>
                @else
                    <img src="{{ $aboutPage->mediaUrl() }}" alt="Media saat ini"
                        class="h-32 w-24 object-cover rounded-lg border border-gray-300 dark:border-gray-700">
                @endif
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                    File Baru <span class="font-normal normal-case text-gray-500">(foto atau video, diupload langsung ke Cloudinary)</span>
                </label>
                <input type="file" id="about-media-file" accept="image/*,video/*"
                    class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white file:mr-3 file:px-3 file:py-1.5 file:text-xs file:font-bold file:uppercase file:border-0 file:rounded-lg file:bg-[#E62C37] file:text-white file:cursor-pointer focus:outline-none focus:border-[#E62C37]">
                <p id="about-media-error" class="hidden mt-2 text-xs font-semibold text-red-500"></p>
                <div id="about-media-preview" class="hidden mt-3"></div>
            </div>
        </div>

        <div class="flex gap-3 justify-end mt-6">
            <button type="button" onclick="closeModal('about-media')"
                class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
            <button type="button" id="about-media-submit-btn" onclick="submitAboutMedia()"
                class="px-4 py-2.5 text-sm font-bold text-white bg-[#E62C37] rounded-xl hover:bg-[#c5242d] transition-colors disabled:opacity-50">Simpan Media</button>
        </div>
    </x-admin.modal>

    {{-- ===================== MODAL LEADERSHIP (CREATE/EDIT) ===================== --}}
    <x-admin.modal id="leader" maxWidth="max-w-xl">
        <x-admin.modal-close id="leader" />
        <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-200 dark:border-gray-800 pr-12">
            <h3 id="leader-modal-title" class="text-lg font-bold uppercase tracking-wide text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 bg-[#E62C37]"></span> Tambah Management
            </h3>
        </div>

        <form id="leader-form" onsubmit="return false;" novalidate>
            <input type="hidden" id="leader-id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Nama <span class="text-[#E62C37]">*</span></label>
                    <input type="text" id="leader-name" placeholder="Nama lengkap..."
                        class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Role (ID) <span class="text-[#E62C37]">*</span></label>
                        <input type="text" id="leader-role" placeholder="cth: Direktur"
                            class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                            Role (EN) <span class="font-normal normal-case text-gray-500">(opsional)</span>
                        </label>
                        <input type="text" id="leader-role-en" placeholder="cth: Director"
                            class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Urutan Tampil</label>
                        <input type="number" id="leader-sort" min="0" value="0"
                            class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                            Foto <span class="font-normal normal-case text-gray-500" id="leader-photo-hint">(*wajib)</span>
                        </label>
                        <input type="file" id="leader-photo" accept="image/*"
                            class="w-full p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white file:mr-3 file:px-3 file:py-1.5 file:text-xs file:font-bold file:uppercase file:border-0 file:rounded-lg file:bg-[#E62C37] file:text-white file:cursor-pointer focus:outline-none focus:border-[#E62C37]">
                    </div>
                </div>
                <img id="leader-photo-preview" src="" alt="" class="hidden h-32 w-32 object-cover rounded-lg border border-gray-300 dark:border-gray-700">
                <p id="leader-error" class="hidden text-xs font-semibold text-red-500"></p>
            </div>

            <div class="flex gap-3 justify-end mt-6">
                <button type="button" onclick="closeModal('leader')"
                    class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
                <button type="button" id="leader-submit-btn" onclick="submitLeader()"
                    class="px-4 py-2.5 text-sm font-bold text-white bg-[#E62C37] rounded-xl hover:bg-[#c5242d] transition-colors disabled:opacity-50">Simpan</button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ===================== MODAL KONFIRMASI HAPUS LEADERSHIP ===================== --}}
    <x-admin.modal id="confirm-delete-leader" maxWidth="max-w-md" zIndex="z-[60]">
        <x-admin.modal-close id="confirm-delete-leader" />
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Leadership</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
            </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
            Yakin menghapus <span id="leader-delete-name" class="font-bold text-gray-900 dark:text-white"></span> dari tim leadership?
        </p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeModal('confirm-delete-leader')"
                class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
            <button type="button" id="confirm-delete-leader-btn"
                class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">Ya, Hapus</button>
        </div>
    </x-admin.modal>

    <script>
        window.AchievementsConfig = {
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
            storeUrl: @json(route('admin.achievements.store')),
            achievementsBaseUrl: @json(url('/admin/achievements')),
            imagesDeleteBaseUrl: @json(url('/admin/achievements/images')),
            storageUrl: @json(asset('storage/achievements')),
            flash: {
                success: @json(session('success')),
                error: @json(session('error')),
            }
        };
    </script>

    {{-- File JS terpisah -- taruh di public/js/achievements.js --}}
    <script type="module" src="{{ asset('js/achievements.js') }}?v={{ filemtime(public_path('js/achievements.js')) }}">
    </script>

    {{-- Seksi About Us: data & URL untuk about-admin.js --}}
    <script>
        window.AboutAdminConfig = {
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
            mediaUpdateUrl: @json(route('admin.about.media.update')),
            leaderStoreUrl: @json(route('admin.about.leadership.store')),
            leadersBaseUrl: @json(url('/admin/about/leaderships')),
            currentMediaUrl: @json($aboutPage->mediaUrl()),
            currentMediaType: @json($aboutPage->media_type),
        };
    </script>
    <script type="module" src="{{ asset('js/about-admin.js') }}?v={{ filemtime(public_path('js/about-admin.js')) }}">
    </script>
@endsection
