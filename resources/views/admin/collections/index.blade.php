@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-white uppercase">Manajemen Koleksi Burung</h2>
            <p class="text-sm text-gray-400 mt-1">Kelola data koleksi burung yang tampil di halaman publik</p>
        </div>
        <button onclick="openCollectionCreateModal()"
            class="action-btn px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] transition-colors duration-200 shadow-md focus:outline-none">
            + Tambah Koleksi
        </button>
    </div>

    {{-- Input search --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" class="table-search-input w-full bg-[#151a22] border border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari koleksi...">
        </div>
    </div>

    <div class="w-full overflow-hidden bg-[#1e2530] border border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-400 bg-[#151a22] border-b border-gray-800">
                        <th class="px-6 py-4">Gambar & Nama</th>
                        <th class="px-6 py-4 w-32">Kategori</th>
                        <th class="px-6 py-4 w-36">Nama Ilmiah</th>
                        <th class="px-6 py-4 w-40 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800 text-gray-300">
                    @forelse($collections as $collection)
                        <tr class="hover:bg-gray-800/50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    @if ($collection->image_path)
                                        <img src="{{ asset('storage/collections/' . $collection->image_path) }}"
                                            class="w-12 h-12 rounded border border-gray-700 object-cover cursor-pointer hover:scale-125 hover:z-10 hover:border-[#E62C37] shadow-sm transition-all duration-200 relative"
                                            onclick="zoomImage(this.src)" alt="{{ $collection->name }}">
                                    @else
                                        <div class="w-12 h-12 rounded bg-gray-800 border border-gray-700 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-white uppercase text-sm">{{ $collection->name }}</div>
                                        @if ($collection->name_en)
                                            <div class="text-xs text-gray-400 mt-0.5">{{ $collection->name_en }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold border border-gray-700 bg-[#151a22] rounded">{{ $collection->category }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs italic text-gray-400">{{ $collection->scientific_name ?: '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button onclick='openCollectionEditModal({{ json_encode($collection) }})'
                                        class="action-btn px-3 py-1.5 text-xs font-bold uppercase tracking-wider border-2 border-amber-500 text-amber-500 hover:bg-amber-500 hover:text-white transition-all duration-200 focus:outline-none">Ubah</button>
                                    <button onclick="openCollectionDeleteModal({{ $collection->id }}, '{{ addslashes($collection->name) }}')"
                                        class="action-btn px-3 py-1.5 text-xs font-bold uppercase tracking-wider border-2 border-[#E62C37] text-[#E62C37] hover:bg-[#E62C37] hover:text-white transition-all duration-200 focus:outline-none">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-sm text-center text-gray-500 bg-[#151a22]">Belum ada data koleksi burung.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===================== MODAL CREATE ===================== --}}
    <x-admin.modal id="create-collection" maxWidth="max-w-2xl">
        <x-admin.modal-close id="create-collection" />
        <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-800 pr-12">
            <h3 class="text-lg font-bold uppercase tracking-wide text-white flex items-center gap-2">
                <span class="w-2 h-2 bg-[#E62C37]"></span> Formulir Input Koleksi Baru
            </h3>
        </div>
        <form id="form-create-collection" action="{{ route('admin.collections.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nama Burung (ID)</label>
                    <input type="text" id="create-col-name" name="name" required
                        class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-[#E62C37]">
                    <span id="error-create-col-name" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Nama wajib diisi</span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nama Burung (EN)</label>
                    <input type="text" name="name_en"
                        class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-[#E62C37]">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nama Ilmiah</label>
                <input type="text" name="scientific_name" placeholder="Contoh: Ara ararauna"
                    class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-[#E62C37]">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Kategori (ID)</label>
                    <input type="text" id="create-col-category" name="category" required placeholder="Contoh: Macaw"
                        class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-[#E62C37]">
                    <span id="error-create-col-category" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Kategori wajib diisi</span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Kategori (EN)</label>
                    <input type="text" name="category_en" placeholder="Contoh: Macaw"
                        class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-[#E62C37]">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Foto Burung</label>
                <div id="dz-collection-create"
                    class="dropzone !bg-[#151a22] border-2 border-dashed border-gray-700 rounded p-6 text-center cursor-pointer hover:border-[#E62C37] transition-colors min-h-[140px]">
                    <div class="dz-message text-sm text-gray-400">Tarik file foto ke sini atau klik untuk memilih</div>
                </div>
                <span id="error-create-col-photo" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Foto wajib diunggah</span>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-gray-800">
                <button type="button" onclick="closeModal('create-collection')"
                    class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-300 bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
                <button type="button" id="submit-create-collection-btn"
                    class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] transition-colors rounded-xl">Simpan</button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ===================== MODAL EDIT ===================== --}}
    <x-admin.modal id="edit-collection" maxWidth="max-w-2xl">
        <x-admin.modal-close id="edit-collection" color="hover:bg-amber-500" />
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-800 pr-12">
            <h3 class="text-lg font-bold uppercase tracking-wide text-white flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-amber-950 text-amber-500 font-black text-sm">!</span>
                Modifikasi Koleksi Terpilih
            </h3>
        </div>
        <form id="form-edit-collection" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nama Burung (ID)</label>
                    <input type="text" id="edit-col-name" name="name" required
                        class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-amber-500">
                    <span id="error-edit-col-name" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Nama wajib diisi</span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nama Burung (EN)</label>
                    <input type="text" id="edit-col-name-en" name="name_en"
                        class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-amber-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Nama Ilmiah</label>
                <input type="text" id="edit-col-scientific" name="scientific_name"
                    class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-amber-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Kategori (ID)</label>
                    <input type="text" id="edit-col-category" name="category" required
                        class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-amber-500">
                    <span id="error-edit-col-category" class="hidden text-xs font-semibold text-[#E62C37] mt-1">Kategori wajib diisi</span>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Kategori (EN)</label>
                    <input type="text" id="edit-col-category-en" name="category_en"
                        class="w-full p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-amber-500">
                </div>
            </div>
            {{-- Foto tersimpan --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Foto Tersimpan</label>
                <div id="edit-col-existing-photo" class="flex flex-wrap gap-2 p-3 bg-[#151a22] border border-gray-700 rounded min-h-[70px]"></div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Upload Foto Baru</label>
                <div id="dz-collection-edit"
                    class="dropzone !bg-[#151a22] border-2 border-dashed border-gray-700 rounded p-6 text-center cursor-pointer hover:border-amber-500 transition-colors min-h-[140px]">
                    <div class="dz-message text-sm text-gray-400">Tarik file foto baru ke sini</div>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-gray-800">
                <button type="button" onclick="closeModal('edit-collection')"
                    class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-300 bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
                <button type="button" id="submit-edit-collection-btn"
                    class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-amber-500 hover:bg-amber-600 transition-colors rounded-xl">Perbarui</button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ===================== MODAL DELETE ===================== --}}
    <x-admin.modal-confirm-delete id="delete-collection" formId="form-delete-collection" title="Konfirmasi Hapus Koleksi">
        Apakah Anda yakin menghapus koleksi <span id="delete-collection-name" class="font-bold text-white"></span>
    </x-admin.modal-confirm-delete>

    {{-- ===================== MODAL KONFIRMASI HAPUS FOTO ===================== --}}
    <x-admin.modal id="confirm-delete-col-photo" maxWidth="max-w-md" zIndex="z-[60]">
        <x-admin.modal-close id="confirm-delete-col-photo" />
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Hapus Foto</h3>
                <p class="text-sm text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
            </div>
        </div>
        <p class="text-sm text-gray-300 mb-6">Apakah Anda yakin menghapus foto koleksi ini secara permanen?</p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeModal('confirm-delete-col-photo')"
                class="px-4 py-2.5 text-sm font-semibold text-gray-300 bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">Batal</button>
            <button type="button" id="confirm-delete-col-photo-btn"
                class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">Ya, Hapus</button>
        </div>
    </x-admin.modal>

    <script>
        window.CollectionsConfig = {
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
            storeUrl: @json(route('admin.collections.store')),
            collectionsBaseUrl: @json(url('/admin/collections')),
            storageUrl: @json(asset('storage/collections')),
            flash: {
                success: @json(session('success')),
                error: @json(session('error')),
            }
        };
    </script>
    <script src="{{ asset('js/collections.js') }}?v={{ filemtime(public_path('js/collections.js')) }}"></script>
@endsection
