@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Percakapan Pelanggan</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $pelanggan->nama ?? $pelanggan->nomor_wa }} — Sesi: {{ ucfirst($pelanggan->sesi_aktif) }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.chatbot.chat', ['pelanggan_id' => $pelanggan->id]) }}"
                class="px-4 py-2 text-sm font-bold uppercase tracking-wider text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-lg transition-colors">
                Buka Chat
            </a>
            <a href="{{ route('admin.chatbot.index') }}"
                class="px-4 py-2 text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 border border-gray-600 hover:border-gray-500 rounded-lg transition-colors">
                Kembali
            </a>
        </div>
    </div>

    {{-- Info Pelanggan + Toggle --}}
    <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4 mb-6 grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Nomor WA</p>
            @php
                $nomorRaw = str_replace(['@s.whatsapp.net', '@lid'], '', $pelanggan->nomor_wa);
                $isLid = str_contains($pelanggan->nomor_wa, '@lid');
                $nomorFormatted = '';
                if (!$isLid && strlen($nomorRaw) > 5) {
                    if (str_starts_with($nomorRaw, '62')) {
                        $nomorFormatted = '+' . substr($nomorRaw, 0, 2) . ' ' . substr($nomorRaw, 2);
                    } else {
                        $nomorFormatted = $nomorRaw;
                    }
                }
            @endphp
            <p class="text-sm text-gray-900 dark:text-white font-medium">
                @if($isLid)
                    WA ID: {{ $nomorRaw }}
                @elseif($nomorFormatted)
                    {{ $nomorFormatted }}
                @else
                    {{ $nomorRaw }}
                @endif
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Nama</p>
            <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $pelanggan->nama ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Sesi Aktif</p>
            <p class="text-sm text-gray-900 dark:text-white font-medium">
                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase
                    {{ $pelanggan->sesi_aktif === 'human' ? 'bg-green-500/20 text-green-400' : '' }}
                    {{ $pelanggan->sesi_aktif === 'ai' ? 'bg-purple-500/20 text-purple-400' : '' }}
                    {{ $pelanggan->sesi_aktif === 'checkout' ? 'bg-amber-500/20 text-amber-400' : '' }}
                    {{ $pelanggan->sesi_aktif === 'inventory' ? 'bg-sky-500/20 text-sky-400' : '' }}
                    {{ $pelanggan->sesi_aktif === 'menu' ? 'bg-gray-500/20 text-gray-500 dark:text-gray-400' : '' }}">
                    {{ $pelanggan->sesi_aktif === 'human' ? 'Admin (Human)' : ($pelanggan->sesi_aktif === 'ai' ? 'AI Bot' : ucfirst($pelanggan->sesi_aktif)) }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Chat</p>
            <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $riwayat->total() }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Toggle Mode</p>
            <div class="flex items-center gap-2 mt-1">
                <span id="modeLabel" class="text-xs font-semibold uppercase tracking-wider
                    {{ $pelanggan->sesi_aktif === 'human' ? 'text-green-400' : 'text-purple-400' }}">
                    {{ $pelanggan->sesi_aktif === 'human' ? 'Admin' : 'AI' }}
                </span>
                <button id="toggleBtn" onclick="toggleModePercakapan()"
                    class="relative inline-flex h-6 w-12 items-center rounded-full transition-colors duration-300 focus:outline-none
                    {{ $pelanggan->sesi_aktif === 'human' ? 'bg-green-500' : 'bg-purple-500' }}">
                    <span id="toggleDot"
                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-300
                        {{ $pelanggan->sesi_aktif === 'human' ? 'translate-x-7' : 'translate-x-1' }}">
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Riwayat Percakapan --}}
    <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Riwayat Percakapan</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-800 max-h-[600px] overflow-y-auto">
            @forelse($riwayat as $chat)
                <div class="px-6 py-3">
                    <div class="flex items-start gap-3">
                        {{-- Pesan Pelanggan --}}
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-semibold text-blue-400">Pelanggan</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $chat->created_at->timezone('Asia/Jakarta')->format('d M Y H:i:s') }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold
                                    {{ $chat->sumber_balasan === 'groq_ai' ? 'bg-purple-500/20 text-purple-400' : '' }}
                                    {{ $chat->sumber_balasan === 'apriori' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $chat->sumber_balasan === 'manual' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $chat->sumber_balasan === 'human' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $chat->sumber_balasan === 'admin' ? 'bg-[#E62C37]/20 text-[#E62C37]' : '' }}
                                    {{ $chat->sumber_balasan === 'system' ? 'bg-gray-600/20 text-gray-500 dark:text-gray-400' : '' }}
                                    {{ $chat->sumber_balasan === 'menu' ? 'bg-gray-500/20 text-gray-500 dark:text-gray-400' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $chat->sumber_balasan)) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $chat->pesan_pengirim }}</p>
                        </div>
                    </div>

                    {{-- Balasan Bot --}}
                    @if($chat->pesan_balasan)
                        <div class="mt-2 ml-8 pl-4 border-l-2 border-[#E62C37]/30">
                            <span class="text-xs font-semibold
                                {{ $chat->sumber_balasan === 'admin' ? 'text-[#E62C37]' : '' }}
                                {{ $chat->sumber_balasan === 'groq_ai' ? 'text-purple-400' : '' }}
                                {{ $chat->sumber_balasan === 'system' ? 'text-gray-500 dark:text-gray-400' : '' }}
                                {{ $chat->sumber_balasan === 'apriori' ? 'text-green-400' : '' }}
                                {{ $chat->sumber_balasan === 'menu' ? 'text-gray-500 dark:text-gray-400' : '' }}">
                                {{ $chat->sumber_balasan === 'admin' ? 'Admin' : ($chat->sumber_balasan === 'groq_ai' ? 'AI Bot' : ($chat->sumber_balasan === 'system' ? 'System' : 'Bot')) }}
                            </span>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 whitespace-pre-line">{{ $chat->pesan_balasan }}</p>
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                    Belum ada percakapan.
                    Belum ada percakapan.
                </div>
            @endforelse
        </div>
    </div>

    <div class="mb-10">
        {{ $riwayat->links() }}
    </div>

    <script>
        const PELANGGAN_ID = {{ $pelanggan->id }};
        const CHAT_TOGGLE_URL = '{{ route("admin.chatbot.chat.toggle") }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        let currentMode = '{{ $pelanggan->sesi_aktif }}';

        async function toggleModePercakapan() {
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
                        pelanggan_id: PELANGGAN_ID,
                        mode: newMode,
                    }),
                });

                const data = await res.json();
                if (res.ok && data.status === 'OK') {
                    currentMode = data.new_mode;
                    updateToggleUIPercakapan();
                    showToast('success', 'Berhasil', `Mode diubah ke ${currentMode === 'human' ? 'Admin (Human)' : 'AI Bot'}`);
                } else {
                    showToast('error', 'Gagal', data.error || data.message || 'Gagal mengubah mode');
                }
            } catch (e) {
                showToast('error', 'Gagal', 'Gagal mengubah mode: ' + e.message);
            }
        }
                if (data.status === 'OK') {
                    currentMode = data.new_mode;
                    updateToggleUIPercakapan();
                    showToast('success', 'Berhasil', `Mode diubah ke ${currentMode === 'human' ? 'Admin (Human)' : 'AI Bot'}`);
                }
            } catch (e) {
                showToast('error', 'Gagal', 'Gagal mengubah mode');
            }
        }

        function updateToggleUIPercakapan() {
            const btn = document.getElementById('toggleBtn');
            const dot = document.getElementById('toggleDot');
            const label = document.getElementById('modeLabel');
            if (!btn || !dot || !label) return;

            if (currentMode === 'human') {
                btn.classList.remove('bg-purple-500');
                btn.classList.add('bg-green-500');
                dot.classList.remove('translate-x-1');
                dot.classList.add('translate-x-7');
                label.textContent = 'Admin';
                label.classList.remove('text-purple-400');
                label.classList.add('text-green-400');
            } else {
                btn.classList.remove('bg-green-500');
                btn.classList.add('bg-purple-500');
                dot.classList.remove('translate-x-7');
                dot.classList.add('translate-x-1');
                label.textContent = 'AI';
                label.classList.remove('text-green-400');
                label.classList.add('text-purple-400');
            }
        }
    </script>
@endsection