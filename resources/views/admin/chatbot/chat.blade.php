@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-white uppercase">WhatsApp Chat</h2>
            <p class="text-sm text-gray-400 mt-1">Kelola percakapan pelanggan — ambil alih dari AI atau kembalikan ke bot</p>
        </div>
        <a href="{{ route('admin.chatbot.index') }}"
            class="px-4 py-2 text-sm font-bold uppercase tracking-wider text-gray-300 border border-gray-600 hover:border-gray-500 rounded-lg transition-colors">
            Kembali
        </a>
    </div>

    <div class="flex gap-4 mb-10" style="height: calc(100vh - 220px); min-height: 500px;" x-data="{ showChat: {{ $selectedPelanggan ? 'true' : 'false' }} }">

        {{-- ==================== KIRI: Daftar Pelanggan ==================== --}}
        <div class="w-full lg:w-80 flex-shrink-0 bg-[#1e2530] border border-gray-800 rounded-lg flex flex-col overflow-hidden"
            :class="{ 'hidden lg:flex': showChat, 'flex': !showChat }">
            <div class="px-4 py-3 border-b border-gray-800">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Pelanggan</h3>
                <input type="text" id="searchPelanggan" placeholder="Cari nama/nomor..."
                    class="mt-2 w-full px-3 py-1.5 text-sm bg-[#151a22] border border-gray-700 rounded text-gray-300 placeholder-gray-500 focus:outline-none focus:border-[#E62C37]">
            </div>
            <div id="daftarPelanggan" class="flex-1 overflow-y-auto divide-y divide-gray-800">
                @forelse($pelangganList as $p)
                    <a href="{{ route('admin.chatbot.chat', ['pelanggan_id' => $p->id]) }}"
                        @click="showChat = true"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-[#151a22] transition-colors {{ optional($selectedPelanggan)->id === $p->id ? 'bg-[#151a22] border-l-2 border-[#E62C37]' : '' }}"
                        data-nama="{{ strtolower($p->nama ?? $p->nomor_wa) }}">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold
                            {{ $p->sesi_aktif === 'human' ? 'bg-green-500/20 text-green-400' : '' }}
                            {{ $p->sesi_aktif === 'ai' ? 'bg-purple-500/20 text-purple-400' : '' }}
                            {{ $p->sesi_aktif === 'manual' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                            {{ $p->sesi_aktif === 'menu' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                            {{ strtoupper(substr($p->nama ?? $p->nomor_wa, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-white truncate">{{ $p->nama ?? 'Tanpa Nama' }}</p>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $p->sesi_aktif === 'human' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $p->sesi_aktif === 'ai' ? 'bg-purple-500/20 text-purple-400' : '' }}
                                    {{ $p->sesi_aktif === 'manual' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $p->sesi_aktif === 'menu' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                                    {{ $p->sesi_aktif === 'human' ? 'ADMIN' : ($p->sesi_aktif === 'ai' ? 'AI' : ($p->sesi_aktif === 'manual' ? 'MANUAL' : 'IDLE')) }}
                                </span>
                            </div>
                            @php
                                $nomorRaw = str_replace(['@s.whatsapp.net', '@lid'], '', $p->nomor_wa);
                                $isLid = str_contains($p->nomor_wa, '@lid');
                                $nomorFormatted = '';
                                if (!$isLid && strlen($nomorRaw) > 5) {
                                    if (str_starts_with($nomorRaw, '62')) {
                                        $nomorFormatted = '+' . substr($nomorRaw, 0, 2) . ' ' . substr($nomorRaw, 2);
                                    } else {
                                        $nomorFormatted = $nomorRaw;
                                    }
                                }
                            @endphp
                            <p class="text-xs text-gray-400 truncate">
                                @if($isLid)
                                    WA ID: {{ $nomorRaw }}
                                @elseif($nomorFormatted)
                                    {{ $nomorFormatted }}
                                @else
                                    {{ $nomorRaw }}
                                @endif
                            </p>
                            @if($p->pesan_terakhir)
                                <p class="text-[10px] text-gray-500 mt-0.5">{{ $p->pesan_terakhir->format('d M H:i') }}</p>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-10 text-center text-gray-500 text-sm">
                        Belum ada pelanggan.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ==================== TENGAH: Chat Window ==================== --}}
        <div class="flex-1 bg-[#1e2530] border border-gray-800 rounded-lg flex flex-col overflow-hidden min-w-0"
            :class="{ 'flex': showChat, 'hidden lg:flex': !showChat }">

            @if($selectedPelanggan)
                {{-- Header Chat --}}
                <div class="px-4 sm:px-6 py-3 border-b border-gray-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <button @click="showChat = false" class="lg:hidden shrink-0 p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-gray-700/50 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        </button>
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold
                            {{ $selectedPelanggan->sesi_aktif === 'human' ? 'bg-green-500/20 text-green-400' : 'bg-purple-500/20 text-purple-400' }}">
                            {{ strtoupper(substr($selectedPelanggan->nama ?? $selectedPelanggan->nomor_wa, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">{{ $selectedPelanggan->nama ?? 'Tanpa Nama' }}</p>
                            @php
                                $nomorRaw = str_replace(['@s.whatsapp.net', '@lid'], '', $selectedPelanggan->nomor_wa);
                                $isLid = str_contains($selectedPelanggan->nomor_wa, '@lid');
                                // Format nomor HP: 628xxx -> +62 8xxx atau 08xxx
                                $nomorFormatted = '';
                                if (!$isLid && strlen($nomorRaw) > 5) {
                                    if (str_starts_with($nomorRaw, '62')) {
                                        $nomorFormatted = '+' . substr($nomorRaw, 0, 2) . ' ' . substr($nomorRaw, 2);
                                    } else {
                                        $nomorFormatted = $nomorRaw;
                                    }
                                }
                            @endphp
                            <p class="text-xs text-gray-400">
                                @if($isLid)
                                    WA ID: {{ $nomorRaw }}
                                @elseif($nomorFormatted)
                                    WA: {{ $nomorFormatted }}
                                @else
                                    {{ $nomorRaw }}
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Toggle Bot ↔ Human --}}
                    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                        <button onclick="clearChat()"
                            class="px-2 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-gray-300 border border-gray-600 hover:border-gray-500 hover:text-white rounded-lg transition-colors">
                            Clear
                        </button>
                        <span class="text-xs font-semibold uppercase tracking-wider
                            {{ $selectedPelanggan->sesi_aktif === 'human' ? 'text-green-400' : 'text-purple-400' }}">
                            {{ $selectedPelanggan->sesi_aktif === 'human' ? 'Admin' : 'AI' }}
                        </span>
                        <button id="toggleBtn" onclick="toggleMode()"
                            class="relative inline-flex h-7 w-14 items-center rounded-full transition-colors duration-300 focus:outline-none
                            {{ $selectedPelanggan->sesi_aktif === 'human' ? 'bg-green-500' : 'bg-purple-500' }}">
                            <span id="toggleDot"
                                class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform duration-300
                                {{ $selectedPelanggan->sesi_aktif === 'human' ? 'translate-x-8' : 'translate-x-1' }}">
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Pesan --}}
                <div id="chatContainer" class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
                    @foreach($riwayat as $chat)
                        {{-- Pesan dari pelanggan --}}
                        @if($chat->pesan_pengirim)
                            <div class="flex justify-start" data-msg-id="{{ $chat->id }}">
                                <div class="max-w-[70%] bg-[#2a3343] rounded-2xl rounded-tl-sm px-4 py-2.5">
                                    <p class="text-sm text-gray-200 whitespace-pre-line">{{ $chat->pesan_pengirim }}</p>
                                    <p class="text-[10px] text-gray-500 mt-1 text-right">{{ $chat->created_at->format('H:i') }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Balasan dari bot/admin/system --}}
                        @if($chat->pesan_balasan)
                            <div class="flex justify-end" data-msg-id="{{ $chat->id }}">
                                <div class="max-w-[70%] rounded-2xl rounded-tr-sm px-4 py-2.5
                                    {{ $chat->sumber_balasan === 'admin' ? 'bg-[#E62C37]/20 border border-[#E62C37]/30' : '' }}
                                    {{ $chat->sumber_balasan === 'groq_ai' ? 'bg-purple-500/20 border border-purple-500/30' : '' }}
                                    {{ $chat->sumber_balasan === 'system' ? 'bg-gray-600/20 border border-gray-600/30' : '' }}
                                    {{ $chat->sumber_balasan === 'apriori' ? 'bg-green-500/20 border border-green-500/30' : '' }}
                                    {{ $chat->sumber_balasan === 'menu' ? 'bg-gray-600/20 border border-gray-600/30' : '' }}">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider
                                            {{ $chat->sumber_balasan === 'admin' ? 'text-[#E62C37]' : '' }}
                                            {{ $chat->sumber_balasan === 'groq_ai' ? 'text-purple-400' : '' }}
                                            {{ $chat->sumber_balasan === 'system' ? 'text-gray-400' : '' }}
                                            {{ $chat->sumber_balasan === 'apriori' ? 'text-green-400' : '' }}">
                                            {{ $chat->sumber_balasan === 'admin' ? 'Admin' : ($chat->sumber_balasan === 'groq_ai' ? 'AI Bot' : ($chat->sumber_balasan === 'system' ? 'System' : ($chat->sumber_balasan === 'apriori' ? 'Rekomendasi' : 'Bot'))) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-200 whitespace-pre-line">{{ $chat->pesan_balasan }}</p>
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <span class="text-[10px] text-gray-500">{{ $chat->created_at->format('H:i') }}</span>
                                        {{-- Indikator centang untuk pesan admin --}}
                                        @if($chat->sumber_balasan === 'admin')
                                            @if($chat->terkirim)
                                                {{-- Centang 2 abu-abu = berhasil terkirim ke WA user --}}
                                                <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 8l4 4L13 4"></path>
                                                    <path d="M7 8l4 4L19 4"></path>
                                                </svg>
                                            @else
                                                {{-- Centang 1 abu-abu = belum terkirim (user offline/no internet) --}}
                                                <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 8l4 4L13 4"></path>
                                                </svg>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Input Kirim Pesan --}}
                <div class="px-4 py-3 border-t border-gray-800" id="inputArea">
                    @php
                        $isAiMode = $selectedPelanggan && ($selectedPelanggan->sesi_aktif === 'ai' || $selectedPelanggan->sesi_aktif === 'menu');
                    @endphp

                    <div id="aiModeNotice" class="{{ $isAiMode ? '' : 'hidden' }} flex items-center gap-2 px-4 py-2.5 bg-purple-500/10 border border-purple-500/20 rounded-xl">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span class="text-sm text-purple-400">Mode AI aktif — switch ke Human untuk mengirim pesan</span>
                    </div>

                    <form id="chatForm" onsubmit="kirimPesan(event)" class="{{ $isAiMode ? 'hidden' : '' }} flex gap-3">
                        <input type="text" id="inputPesan" placeholder="Ketik pesan..."
                            autocomplete="off"
                            class="flex-1 px-4 py-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-gray-200 placeholder-gray-500 focus:outline-none focus:border-[#E62C37]">
                        <button type="submit" id="btnKirim"
                            class="px-5 py-2.5 bg-[#E62C37] hover:bg-[#c5242d] text-white text-sm font-bold rounded-xl transition-colors">
                            Kirim
                        </button>
                    </form>
                </div>

            @else
                {{-- Placeholder jika belum pilih pelanggan --}}
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                        <p class="text-gray-500 text-sm">Pilih pelanggan dari daftar untuk memulai percakapan</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Konfirmasi Clear Chat --}}
    <div id="modalClearChat" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-[#1e2530] border border-gray-700 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Hapus Percakapan</h3>
                    <p class="text-sm text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            <p class="text-sm text-gray-300 mb-6">Apakah Anda yakin ingin menghapus <span class="font-semibold text-white">semua percakapan</span> dengan pelanggan ini? Chat yang sudah dihapus tidak bisa dikembalikan.</p>
            <div class="flex gap-3 justify-end">
                <button onclick="tutupModalClearChat()" class="px-4 py-2.5 text-sm font-semibold text-gray-300 bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                    Batal
                </button>
                <button onclick="konfirmasiClearChat()" class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">
                    Ya, Hapus Semua
                </button>
            </div>
        </div>
    </div>

    {{-- Data untuk JavaScript --}}
    <script>
        const SELECTED_PELANGGAN_ID = {{ optional($selectedPelanggan)->id ?? 'null' }};
        const CHAT_SEND_URL = '{{ route("admin.chatbot.chat.send") }}';
        const CHAT_TOGGLE_URL = '{{ route("admin.chatbot.chat.toggle") }}';
        const CHAT_CLEAR_URL = '{{ route("admin.chatbot.chat.clear", ["pelanggan" => "__ID__"]) }}';
        const CHAT_MESSAGES_URL = '{{ route("admin.chatbot.chat.messages", ["pelanggan" => "__ID__"]) }}';
        const CHAT_PELANGGAN_LIST_URL = '{{ route("admin.chatbot.chat", ["pelanggan_id" => "__ID__"]) }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        let currentMode = '{{ optional($selectedPelanggan)->sesi_aktif ?? "ai" }}';
        let lastMessageId = 0;
        let pollingInterval = null;
        let sidebarPollingInterval = null;

        // Scroll ke bawah
        function scrollToBottom() {
            const container = document.getElementById('chatContainer');
            if (container) container.scrollTop = container.scrollHeight;
        }

        // Cari pelanggan
        document.getElementById('searchPelanggan')?.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#daftarPelanggan a[data-nama]').forEach(function (el) {
                el.style.display = el.dataset.nama.includes(q) ? '' : 'none';
            });
        });

        // Buka modal clear chat
        function clearChat() {
            if (!SELECTED_PELANGGAN_ID) return;
            const modal = document.getElementById('modalClearChat');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Tutup modal clear chat
        function tutupModalClearChat() {
            const modal = document.getElementById('modalClearChat');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Eksekusi hapus chat setelah konfirmasi
        async function konfirmasiClearChat() {
            tutupModalClearChat();

            try {
                const url = CHAT_CLEAR_URL.replace('__ID__', SELECTED_PELANGGAN_ID);
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                });

                const data = await res.json();
                if (res.ok && data.status === 'OK') {
                    const container = document.getElementById('chatContainer');
                    if (container) container.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-gray-500 text-sm">Belum ada percakapan.</p></div>';
                    lastMessageId = 0;
                    showToast('success', 'Berhasil', 'Semua percakapan berhasil dihapus.');
                } else {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus percakapan');
                }
            } catch (e) {
                showToast('error', 'Gagal', 'Gagal menghapus percakapan: ' + e.message);
            }
        }

        // Toggle mode bot/human
        async function toggleMode() {
            if (!SELECTED_PELANGGAN_ID) return;

            const newMode = currentMode === 'human' ? 'ai' : 'human';

            try {
                const res = await fetch(CHAT_TOGGLE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({
                        pelanggan_id: SELECTED_PELANGGAN_ID,
                        mode: newMode,
                    }),
                });

                const data = await res.json();
                if (res.ok && data.status === 'OK') {
                    currentMode = data.new_mode;
                    updateToggleUI();
                    showToast('success', 'Berhasil', `Mode diubah ke ${currentMode === 'human' ? 'Admin (Human)' : 'AI Bot'}`);
                } else {
                    showToast('error', 'Gagal', data.error || data.message || 'Gagal mengubah mode');
                }
            } catch (e) {
                showToast('error', 'Gagal', 'Gagal mengubah mode: ' + e.message);
            }
        }

        function updateToggleUI() {
            const btn = document.getElementById('toggleBtn');
            const dot = document.getElementById('toggleDot');
            if (!btn || !dot) return;

            if (currentMode === 'human') {
                btn.classList.remove('bg-purple-500');
                btn.classList.add('bg-green-500');
                dot.classList.remove('translate-x-1');
                dot.classList.add('translate-x-8');
            } else {
                btn.classList.remove('bg-green-500');
                btn.classList.add('bg-purple-500');
                dot.classList.remove('translate-x-8');
                dot.classList.add('translate-x-1');
            }

            // Update label
            const label = btn.parentElement?.querySelector('span');
            if (label) {
                if (currentMode === 'human') {
                    label.textContent = 'Admin (Human)';
                    label.className = 'text-xs font-semibold uppercase tracking-wider text-green-400';
                } else {
                    label.textContent = 'AI Bot';
                    label.className = 'text-xs font-semibold uppercase tracking-wider text-purple-400';
                }
            }

            // Update input area visibility
            updateInputArea();

            // Update badge di sidebar
            updateSidebarBadge(SELECTED_PELANGGAN_ID, currentMode);
        }

        function updateInputArea() {
            const aiNotice = document.getElementById('aiModeNotice');
            const chatForm = document.getElementById('chatForm');
            if (!aiNotice || !chatForm) return;

            if (!SELECTED_PELANGGAN_ID) {
                aiNotice.classList.add('hidden');
                chatForm.classList.add('hidden');
                return;
            }

            if (currentMode === 'ai' || currentMode === 'menu') {
                aiNotice.classList.remove('hidden');
                chatForm.classList.add('hidden');
            } else {
                aiNotice.classList.add('hidden');
                chatForm.classList.remove('hidden');
            }
        }

        // Update badge mode di sidebar
        function updateSidebarBadge(pelangganId, mode) {
            const link = document.querySelector(`#daftarPelanggan a[href*="pelanggan_id=${pelangganId}"]`);
            if (!link) return;

            const badge = link.querySelector('.rounded.text-\\[10px\\]');
            if (badge) {
                let label = 'IDLE';
                let colorClass = 'bg-gray-500/20 text-gray-400';
                if (mode === 'human') { label = 'ADMIN'; colorClass = 'bg-green-500/20 text-green-400'; }
                else if (mode === 'ai') { label = 'AI'; colorClass = 'bg-purple-500/20 text-purple-400'; }
                else if (mode === 'manual') { label = 'MANUAL'; colorClass = 'bg-yellow-500/20 text-yellow-400'; }
                badge.textContent = label;
                badge.className = `px-1.5 py-0.5 rounded text-[10px] font-bold uppercase ${colorClass}`;
            }
        }

        // Kirim pesan
        async function kirimPesan(e) {
            e.preventDefault();
            if (!SELECTED_PELANGGAN_ID) return;

            const input = document.getElementById('inputPesan');
            const btn = document.getElementById('btnKirim');
            const pesan = input.value.trim();
            if (!pesan) return;

            btn.disabled = true;
            btn.textContent = '...';

            try {
                const res = await fetch(CHAT_SEND_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        pelanggan_id: SELECTED_PELANGGAN_ID,
                        pesan: pesan,
                    }),
                });

                const data = await res.json();
                if (res.ok && data.status === 'OK') {
                    tambahPesanKeChat(pesan, data.message_id, 'admin', data.sent);
                    input.value = '';
                    if (data.note) {
                        showToast('success', 'Terkirim', data.note);
                    }
                } else {
                    showToast('error', 'Gagal', data.error || data.message || 'Gagal mengirim pesan');
                }
            } catch (err) {
                showToast('error', 'Gagal', 'Gagal mengirim pesan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Kirim';
            }
        }

        // Tambah pesan ke chat window secara lokal
        function tambahPesanKeChat(pesan, msgId, sumber, sent) {
            const container = document.getElementById('chatContainer');
            if (!container) return;

            // Hapus placeholder "Belum ada percakapan" jika ada
            const placeholder = container.querySelector('.flex.items-center.justify-center.h-full');
            if (placeholder) placeholder.remove();

            const now = new Date();
            const jam = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

            if (sumber === 'user') {
                // Pesan dari pelanggan (kiri)
                const div = document.createElement('div');
                div.className = 'flex justify-start';
                div.setAttribute('data-msg-id', msgId || '');
                div.innerHTML = `
                    <div class="max-w-[70%] bg-[#2a3343] rounded-2xl rounded-tl-sm px-4 py-2.5">
                        <p class="text-sm text-gray-200 whitespace-pre-line">${escapeHtml(pesan)}</p>
                        <p class="text-[10px] text-gray-500 mt-1 text-right">${jam}</p>
                    </div>
                `;
                container.appendChild(div);
            } else {
                // Balasan (kanan)
                let bgColor = 'bg-gray-600/20 border border-gray-600/30';
                let labelColor = 'text-gray-400';
                let label = 'Bot';
                let checkmark = '';

                if (sumber === 'admin') {
                    bgColor = 'bg-[#E62C37]/20 border border-[#E62C37]/30';
                    labelColor = 'text-[#E62C37]';
                    label = 'Admin';
                    // Centang 2 = terkirim, Centang 1 = belum terkirim
                    if (sent) {
                        checkmark = '<svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 8l4 4L13 4"></path><path d="M7 8l4 4L19 4"></path></svg>';
                    } else {
                        checkmark = '<svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 8l4 4L13 4"></path></svg>';
                    }
                } else if (sumber === 'groq_ai') {
                    bgColor = 'bg-purple-500/20 border border-purple-500/30';
                    labelColor = 'text-purple-400';
                    label = 'AI Bot';
                } else if (sumber === 'system') {
                    bgColor = 'bg-gray-600/20 border border-gray-600/30';
                    labelColor = 'text-gray-400';
                    label = 'System';
                } else if (sumber === 'apriori') {
                    bgColor = 'bg-green-500/20 border border-green-500/30';
                    labelColor = 'text-green-400';
                    label = 'Rekomendasi';
                } else if (sumber === 'menu') {
                    bgColor = 'bg-gray-600/20 border border-gray-600/30';
                    labelColor = 'text-gray-400';
                    label = 'Bot';
                }

                const div = document.createElement('div');
                div.className = 'flex justify-end';
                div.setAttribute('data-msg-id', msgId || '');
                div.innerHTML = `
                    <div class="max-w-[70%] ${bgColor} rounded-2xl rounded-tr-sm px-4 py-2.5">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider ${labelColor}">${label}</span>
                        </div>
                        <p class="text-sm text-gray-200 whitespace-pre-line">${escapeHtml(pesan)}</p>
                        <div class="flex items-center justify-end gap-1 mt-1">
                            <span class="text-[10px] text-gray-500">${jam}</span>
                            ${checkmark}
                        </div>
                    </div>
                `;
                container.appendChild(div);
            }

            scrollToBottom();
            if (msgId && typeof msgId === 'number') lastMessageId = msgId;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Polling pesan baru
        async function pollingPesan() {
            if (!SELECTED_PELANGGAN_ID) return;

            try {
                const url = CHAT_MESSAGES_URL.replace('__ID__', SELECTED_PELANGGAN_ID) + '?after_id=' + lastMessageId;
                const res = await fetch(url);
                const messages = await res.json();

                if (messages.length > 0) {
                    messages.forEach(function (msg) {
                        if (msg.id > lastMessageId) lastMessageId = msg.id;

                        // Cek duplikat
                        if (document.querySelector('[data-msg-id="' + msg.id + '"]')) return;

                        // Pesan dari pelanggan
                        if (msg.pesan_pengirim) {
                            tambahPesanKeChat(msg.pesan_pengirim, msg.id, 'user');
                        }

                        // Balasan dari bot/admin/system
                        if (msg.pesan_balasan) {
                            const isAdmin = msg.sumber_balasan === 'admin';
                            tambahPesanKeChat(msg.pesan_balasan, msg.id, msg.sumber_balasan || 'bot', isAdmin ? msg.terkirim : true);
                        }
                    });

                    scrollToBottom();
                }
            } catch (e) {
                // Silent fail
            }
        }

        // Inisialisasi
        document.addEventListener('DOMContentLoaded', function () {
            scrollToBottom();
            updateInputArea();

            // Cari lastMessageId dari pesan yang sudah ada
            const existingMsgs = document.querySelectorAll('[data-msg-id]');
            existingMsgs.forEach(function (el) {
                const id = parseInt(el.dataset.msgId);
                if (id > lastMessageId) lastMessageId = id;
            });

            // Mulai polling pesan setiap 2 detik
            if (SELECTED_PELANGGAN_ID) {
                pollingInterval = setInterval(pollingPesan, 2000);
            }
        });

        // Hentikan polling saat pindah halaman
        window.addEventListener('beforeunload', function () {
            if (pollingInterval) clearInterval(pollingInterval);
            if (sidebarPollingInterval) clearInterval(sidebarPollingInterval);
        });
    </script>
@endsection
