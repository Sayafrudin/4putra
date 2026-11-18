@extends('layouts.admin')

@section('content')
    <div class="my-6 border-b border-gray-200 dark:border-gray-800 pb-5">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Edit User</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Perbarui informasi akun {{ $user->name }}</p>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6"
            x-data="{ role: '{{ old('role', $user->role) }}' }">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Nama</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#E62C37]/50 focus:border-[#E62C37] text-sm">
                @error('name')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Username</label>
                <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required
                    class="w-full px-4 py-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#E62C37]/50 focus:border-[#E62C37] text-sm">
                @error('username')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Email <span class="font-normal text-gray-500 dark:text-gray-400">(opsional)</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                    class="w-full px-4 py-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#E62C37]/50 focus:border-[#E62C37] text-sm">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Diisi → email ini bisa masuk via "Masuk dengan Google". Kosongkan → login via username &amp; password.</p>
                @error('email')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Role</label>
                <select name="role" id="role" required x-model="role"
                    class="w-full px-4 py-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#E62C37]/50 focus:border-[#E62C37] text-sm">
                    <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>User</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
                @error('role')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg border border-gray-300 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Hak Akses Menu</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($menuList as $m)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" name="menu_access[]" value="{{ $m['key'] }}" data-akses="{{ $m['akses'] }}"
                                {{ in_array($m['key'], old('menu_access', $user->menu_access ?? [])) ? 'checked' : '' }}
                                class="menu-akses-item rounded border-gray-400 dark:border-gray-600 text-[#E62C37] focus:ring-[#E62C37]/50 bg-white dark:bg-gray-800">
                            {{ $m['label'] }}@if($m['akses'] === 'admin') <span class="text-[10px] uppercase tracking-wider text-gray-400">admin</span>@endif
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Role Admin = semua menu otomatis terbuka. Role User = menu bertanda "admin" tidak tersedia. Dashboard & profil selalu tersedia.</p>
            </div>

            <div class="border-t border-gray-300 dark:border-gray-700 pt-6">
                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-4">Ubah Password <span class="text-gray-500">(kosongkan jika tidak ingin mengubah)</span></h3>

                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Password Baru</label>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" name="password" id="password"
                                class="w-full px-4 py-3 pr-12 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#E62C37]/50 focus:border-[#E62C37] text-sm"
                                placeholder="Minimal 8 karakter">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200 focus:outline-none">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Konfirmasi Password Baru</label>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" name="password_confirmation" id="password_confirmation"
                                class="w-full px-4 py-3 pr-12 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#E62C37]/50 focus:border-[#E62C37] text-sm"
                                placeholder="Ulangi password baru">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200 focus:outline-none">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button type="submit"
                    class="px-6 py-3 bg-[#E62C37] hover:bg-[#c5242d] text-white font-semibold rounded-lg transition-colors duration-200 text-sm">
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.users.index') }}"
                    class="px-6 py-3 text-gray-500 dark:text-gray-400 hover:text-white border border-gray-300 dark:border-gray-700 rounded-lg hover:border-gray-500 transition-colors text-sm">
                    Batal
                </a>
            </div>
        </form>
    </div>
    <script>
        // Role menentukan keadaan checkbox hak akses: Admin = semua tercentang &
        // terkunci (akses penuh); User = menu eksklusif admin terkunci tertutup.
        function terapkanAkses(role) {
            document.querySelectorAll('.menu-akses-item').forEach(function (cb) {
                const adminOnly = cb.dataset.akses === 'admin';
                if (role === 'admin') {
                    cb.checked = true;
                    cb.disabled = true;
                } else {
                    cb.disabled = adminOnly;
                    if (adminOnly) cb.checked = false;
                }
            });
        }
        (function () {
            const roleEl = document.getElementById('role');
            if (!roleEl) return;
            terapkanAkses(roleEl.value);
            roleEl.addEventListener('change', function () { terapkanAkses(roleEl.value); });
        })();
    </script>
@endsection
