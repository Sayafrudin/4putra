@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Manajemen Akun User</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monitoring dan kelola akun pengguna yang memiliki akses ke dashboard</p>
        </div>

        <button onclick="bukaModalTambahUser()"
            class="px-5 py-2.5 text-sm font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] transition-colors duration-200 shadow-md rounded-lg">
            + Tambah User
        </button>
    </div>

    {{-- Input search --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" class="table-search-input w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari user...">
        </div>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Akun</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $users->count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Admin</p>
            <p class="text-2xl font-bold text-[#E62C37] mt-1">{{ $users->where('role', 'admin')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">User Biasa</p>
            <p class="text-2xl font-bold text-gray-600 dark:text-gray-300 mt-1">{{ $users->where('role', 'user')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Online Sekarang</p>
            <p id="online-count" class="text-2xl font-bold text-green-400 mt-1">{{ $users->filter->isOnline()->count() }}</p>
        </div>
    </div>

    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4 w-28">Role</th>
                        <th class="px-6 py-4 w-24">Status</th>
                        <th class="px-6 py-4 w-32">Login Via</th>
                        <th class="px-6 py-4 w-40">Terakhir Login</th>
                        <th class="px-6 py-4 w-48 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($users as $u)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors duration-150">
                            <td class="px-6 py-4 text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <div class="relative">
                                        <div class="w-8 h-8 rounded-full {{ $u->isAdmin() ? 'bg-[#E62C37]/20' : 'bg-gray-200 dark:bg-gray-700' }} flex items-center justify-center shrink-0">
                                            <span class="text-xs font-bold {{ $u->isAdmin() ? 'text-[#E62C37]' : 'text-gray-700 dark:text-gray-300' }}">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                        </div>
                                        <span id="avatar-status-{{ $u->id }}" class="absolute -bottom-0.5 -right-0.5 w-3 h-3 border-2 border-[#1e2530] rounded-full {{ $u->isOnline() ? 'bg-green-400' : 'bg-gray-600' }} transition-colors duration-300"></span>
                                    </div>
                                    <span>{{ $u->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $u->email }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $u->isAdmin() ? 'bg-[#E62C37]/20 text-[#E62C37]' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                                    {{ $u->isAdmin() ? 'Admin' : 'User' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span id="status-text-{{ $u->id }}" class="flex items-center gap-1.5 text-xs {{ $u->isOnline() ? 'text-green-400' : 'text-gray-500' }} transition-colors duration-300">
                                    <span id="status-dot-{{ $u->id }}" class="w-2 h-2 rounded-full {{ $u->isOnline() ? 'bg-green-400 animate-pulse' : 'bg-gray-600' }} transition-colors duration-300"></span>
                                    <span id="status-label-{{ $u->id }}">{{ $u->isOnline() ? 'Online' : 'Offline' }}</span>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($u->google_id)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 font-medium">
                                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                        Google
                                    </span>
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Email/Pass</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($u->last_login_at)
                                    {{ $u->last_login_at->diffForHumans() }}
                                @else
                                    <span class="text-gray-600 italic">Belum pernah</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="window.location='{{ route('admin.users.activity', $u) }}'"
                                        class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-lg hover:border-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                                        Detail
                                    </button>
                                    <button onclick='bukaModalEditUser({{ json_encode($u, JSON_BIGINT_AS_STRING) }})'
                                        class="px-3 py-1.5 text-xs font-semibold text-blue-400 bg-blue-500/10 border border-blue-500/30 rounded-lg hover:bg-blue-500/20 transition-colors">
                                        Edit
                                    </button>
                                    @if($u->id !== auth()->id())
                                        <button onclick="bukaModalHapusUser({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                            class="px-3 py-1.5 text-xs font-semibold text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg hover:bg-red-500/20 transition-colors">
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== MODAL TAMBAH USER ==================== --}}
    <div id="modalTambahUser" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tambah User Baru</h3>
                <button onclick="tutupModal('modalTambahUser')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formTambahUser" onsubmit="submitTambahUser(event)" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama</label>
                    <input type="text" id="tambah_name" placeholder="Nama lengkap"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_name"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" id="tambah_email" placeholder="email@example.com"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_email"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Role</label>
                    <select id="tambah_role"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="tambah_password" placeholder="Minimal 8 karakter"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 pr-12 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <button type="button" @click="show = !show"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_tambah_password"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Konfirmasi Password</label>
                    <input type="password" id="tambah_password_confirmation" placeholder="Ulangi password"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                </div>
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" onclick="tutupModal('modalTambahUser')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnTambahUser"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-[#E62C37] rounded-xl hover:bg-[#c5242d] transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL EDIT USER ==================== --}}
    <div id="modalEditUser" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit User</h3>
                <button onclick="tutupModal('modalEditUser')" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="formEditUser" onsubmit="submitEditUser(event)" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama</label>
                    <input type="text" id="edit_name" placeholder="Nama lengkap"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_name"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" id="edit_email" placeholder="email@example.com"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_email"></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Role</label>
                    <select id="edit_role"
                        class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Password Baru <span class="text-gray-500">(kosongkan jika tidak diubah)</span></label>
                    <div class="relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" id="edit_password" placeholder="Minimal 8 karakter"
                            class="w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 pr-12 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:ring-1 focus:ring-[#E62C37]/50 outline-none">
                        <button type="button" @click="show = !show"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-red-400 mt-1 hidden" id="err_edit_password"></p>
                </div>
                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" onclick="tutupModal('modalEditUser')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnEditUser"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL HAPUS USER ==================== --}}
    <div id="modalHapusUser" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Akun</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">Apakah Anda yakin ingin menghapus akun <span id="hapus_nama_user" class="font-semibold text-gray-900 dark:text-white"></span>?</p>
            <form id="formHapusUser" onsubmit="submitHapusUser(event)">
                @csrf
                @method('DELETE')
                <input type="hidden" id="hapus_id_user">
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="tutupModal('modalHapusUser')"
                        class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnHapusUser"
                        class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const STORE_URL = '{{ route("admin.users.store") }}';
        const CSRF = '{{ csrf_token() }}';
        const BASE_URL = '{{ url("/admin/users") }}';

        // Modal helpers
        function bukaModal(id) {
            const m = document.getElementById(id);
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function tutupModal(id) {
            const m = document.getElementById(id);
            m.classList.remove('flex');
            m.classList.add('hidden');
            clearAllErrors();
        }

        function clearAllErrors() {
            document.querySelectorAll('[id^="err_"]').forEach(el => {
                el.textContent = '';
                el.classList.add('hidden');
            });
        }

        function showError(id, msg) {
            const el = document.getElementById(id);
            if (el) { el.textContent = msg; el.classList.remove('hidden'); }
        }

        // ==================== TAMBAH USER ====================
        function bukaModalTambahUser() {
            document.getElementById('formTambahUser').reset();
            clearAllErrors();
            bukaModal('modalTambahUser');
        }

        async function submitTambahUser(e) {
            e.preventDefault();
            clearAllErrors();
            let valid = true;

            const name = document.getElementById('tambah_name').value.trim();
            const email = document.getElementById('tambah_email').value.trim();
            const password = document.getElementById('tambah_password').value;
            const passwordConf = document.getElementById('tambah_password_confirmation').value;

            if (!name) { showError('err_tambah_name', 'Nama wajib diisi'); valid = false; }
            if (!email) { showError('err_tambah_email', 'Email wajib diisi'); valid = false; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('err_tambah_email', 'Format email tidak valid'); valid = false; }
            if (!password) { showError('err_tambah_password', 'Password wajib diisi'); valid = false; }
            else if (password.length < 8) { showError('err_tambah_password', 'Password minimal 8 karakter'); valid = false; }
            if (password !== passwordConf) { showError('err_tambah_password', 'Konfirmasi password tidak cocok'); valid = false; }

            if (!valid) return;

            const btn = document.getElementById('btnTambahUser');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            try {
                const res = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ name, email, role: document.getElementById('tambah_role').value, password, password_confirmation: passwordConf }),
                });

                const data = await res.json();
                if (res.ok) {
                    tutupModal('modalTambahUser');
                    showToast('success', 'Berhasil', 'User baru berhasil ditambahkan!');
                    setTimeout(() => location.reload(), 500);
                } else {
                    if (data.errors) {
                        if (data.errors.name) showError('err_tambah_name', data.errors.name[0]);
                        if (data.errors.email) showError('err_tambah_email', data.errors.email[0]);
                        if (data.errors.password) showError('err_tambah_password', data.errors.password[0]);
                    } else {
                        showToast('error', 'Gagal', data.message || 'Gagal menambahkan user');
                    }
                }
            } catch (err) {
                showToast('error', 'Gagal', 'Terjadi kesalahan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Simpan';
            }
        }

        // ==================== EDIT USER ====================
        function bukaModalEditUser(user) {
            clearAllErrors();
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_password').value = '';
            bukaModal('modalEditUser');
        }

        async function submitEditUser(e) {
            e.preventDefault();
            clearAllErrors();
            let valid = true;

            const id = document.getElementById('edit_id').value;
            const name = document.getElementById('edit_name').value.trim();
            const email = document.getElementById('edit_email').value.trim();
            const password = document.getElementById('edit_password').value;

            if (!name) { showError('err_edit_name', 'Nama wajib diisi'); valid = false; }
            if (!email) { showError('err_edit_email', 'Email wajib diisi'); valid = false; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('err_edit_email', 'Format email tidak valid'); valid = false; }
            if (password && password.length < 8) { showError('err_edit_password', 'Password minimal 8 karakter'); valid = false; }

            if (!valid) return;

            const btn = document.getElementById('btnEditUser');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            try {
                const body = { name, email, role: document.getElementById('edit_role').value, _method: 'PUT' };
                if (password) { body.password = password; body.password_confirmation = password; }

                const res = await fetch(`${BASE_URL}/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify(body),
                });

                const data = await res.json();
                if (res.ok) {
                    tutupModal('modalEditUser');
                    showToast('success', 'Berhasil', 'Data user berhasil diperbarui!');
                    setTimeout(() => location.reload(), 500);
                } else {
                    if (data.errors) {
                        if (data.errors.name) showError('err_edit_name', data.errors.name[0]);
                        if (data.errors.email) showError('err_edit_email', data.errors.email[0]);
                        if (data.errors.password) showError('err_edit_password', data.errors.password[0]);
                    } else {
                        showToast('error', 'Gagal', data.message || 'Gagal memperbarui user');
                    }
                }
            } catch (err) {
                showToast('error', 'Gagal', 'Terjadi kesalahan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Simpan Perubahan';
            }
        }

        // ==================== HAPUS USER ====================
        function bukaModalHapusUser(id, nama) {
            document.getElementById('hapus_id_user').value = id;
            document.getElementById('hapus_nama_user').textContent = nama;
            bukaModal('modalHapusUser');
        }

        async function submitHapusUser(e) {
            e.preventDefault();
            const id = document.getElementById('hapus_id_user').value;
            const btn = document.getElementById('btnHapusUser');
            btn.disabled = true;
            btn.textContent = 'Menghapus...';

            try {
                const res = await fetch(`${BASE_URL}/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                });

                const data = await res.json();
                if (res.ok) {
                    tutupModal('modalHapusUser');
                    showToast('success', 'Berhasil', 'User berhasil dihapus!');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus user');
                }
            } catch (err) {
                showToast('error', 'Gagal', 'Terjadi kesalahan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Ya, Hapus';
            }
        }
    </script>

    {{-- Realtime Online Status via Firebase RTDB --}}
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js';
        import { getDatabase, ref as rtdbRef, onValue } from 'https://www.gstatic.com/firebasejs/12.16.0/firebase-database.js';

        const firebaseConfig = {
            apiKey: "AIzaSyBKRGNiPcZ-twcR-BxwCyREyATJiQ2VTos",
            authDomain: "putra-project-502403.firebaseapp.com",
            databaseURL: "https://putra-project-502403-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "putra-project-502403",
        };

        const app = initializeApp(firebaseConfig);
        const rtdb = getDatabase(app);

        const userIds = [@foreach($users as $u){{ $u->id }}, @endforeach];
        let onlineCount = 0;
        let previousStatus = {};

        function updateStatus(userId, isOnline) {
            const avatarDot = document.getElementById('avatar-status-' + userId);
            const statusDot = document.getElementById('status-dot-' + userId);
            const statusLabel = document.getElementById('status-label-' + userId);
            const statusText = document.getElementById('status-text-' + userId);

            if (previousStatus[userId] === isOnline) return;
            previousStatus[userId] = isOnline;

            if (avatarDot) {
                avatarDot.classList.remove('bg-green-400', 'bg-gray-600');
                avatarDot.classList.add(isOnline ? 'bg-green-400' : 'bg-gray-600');
            }
            if (statusText) {
                statusText.classList.remove('text-green-400', 'text-gray-500');
                statusText.classList.add(isOnline ? 'text-green-400' : 'text-gray-500');
            }
            if (statusDot) {
                statusDot.classList.remove('bg-green-400', 'bg-gray-600', 'animate-pulse');
                statusDot.classList.add(isOnline ? 'bg-green-400' : 'bg-gray-600');
                if (isOnline) statusDot.classList.add('animate-pulse');
            }
            if (statusLabel) {
                statusLabel.textContent = isOnline ? 'Online' : 'Offline';
            }
        }

        function updateOnlineCount() {
            let count = 0;
            userIds.forEach(function(uid) { if (previousStatus[uid]) count++; });
            const countEl = document.getElementById('online-count');
            if (countEl && count !== onlineCount) {
                onlineCount = count;
                countEl.textContent = onlineCount;
            }
        }

        userIds.forEach(function(userId) {
            previousStatus[userId] = false;
            const presenceRef = rtdbRef(rtdb, 'presence/' + userId);
            onValue(presenceRef, function(snap) {
                const data = snap.val();
                const online = data && data.online === true;
                updateStatus(userId, online);
                updateOnlineCount();
            });
        });
    </script>
@endsection
