@extends('layouts.admin')

@section('content')
    <div
        class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">WhatsApp Chat</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kelola percakapan pelanggan — ambil alih dari AI atau kembalikan ke bot</p>
        </div>
        <a href="{{ route('admin.chatbot.index') }}"
            class="px-4 py-2 text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 border border-gray-600 hover:border-gray-500 rounded-lg transition-colors">
            Kembali
        </a>
    </div>

    <div class="flex gap-4 mb-10" style="height: calc(100vh - 220px); min-height: 500px;" x-data="{ showChat: {{ $selectedPelanggan ? 'true' : 'false' }} }">

        {{-- ==================== KIRI: Daftar Kontak (WhatsApp Web) ==================== --}}
        <div class="w-full lg:w-80 flex-shrink-0 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-700 rounded-xl flex flex-col overflow-hidden"
            :class="{ 'hidden lg:flex': showChat, 'flex': !showChat }">
            <div class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Chats</h3>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $pelangganList->count() }} Kontak</span>
                </div>
                <input type="text" id="searchPelanggan" placeholder="Cari nama/nomor..."
                    class="mt-2.5 w-full px-3.5 py-2 text-sm bg-gray-100 dark:bg-[#151a22] border border-transparent focus:border-gray-300 dark:focus:border-gray-600 rounded-full text-gray-600 dark:text-gray-300 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none transition-colors">
            </div>
            <div id="daftarPelanggan" class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($pelangganList as $p)
                    @php
                        $inisial = strtoupper(substr($p->nama ?? $p->nomor_wa, 0, 2));
                        $dotColor = match ($p->sesi_aktif) {
                            'human' => 'bg-green-500',
                            'ai' => 'bg-purple-500',
                            'checkout' => 'bg-amber-500',
                            'inventory' => 'bg-sky-500',
                            default => 'bg-gray-400',
                        };
                        $preview = $p->pesan_preview ?? str_replace(['@s.whatsapp.net', '@lid'], '', $p->nomor_wa);
                    @endphp
                    <a href="{{ route('admin.chatbot.chat', ['pelanggan_id' => $p->id]) }}" @click="showChat = true"
                        class="relative flex items-center gap-3 px-3 py-3 hover:bg-gray-100 dark:hover:bg-[#151a22] transition-colors {{ optional($selectedPelanggan)->id == $p->id ? 'bg-gray-100 dark:bg-[#151a22]' : '' }}"
                        data-nama="{{ strtolower($p->nama ?? $p->nomor_wa) }}" data-id="{{ $p->id }}">
                        {{-- Avatar placeholder + dot status sesi --}}
                        <div class="relative shrink-0">
                            <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-[#2a3343] flex items-center justify-center text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ $inisial }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-[#1e2530] sesi-dot {{ $dotColor }}"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $p->nama ?? 'Tanpa Nama' }}</p>
                                @if ($p->pesan_terakhir)
                                    <span class="text-[10px] text-gray-400 shrink-0">{{ $p->pesan_terakhir->format('H:i') }}</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($preview, 34) }}</p>
                                @if ($p->unread_count > 0)
                                    <span class="unread-badge shrink-0 bg-green-500 text-white text-[10px] font-bold rounded-full min-w-[20px] h-5 px-1.5 inline-flex items-center justify-center"
                                        data-pelanggan-id="{{ $p->id }}">{{ $p->unread_count }}</span>
                                @endif
                            </div>
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
        <div class="flex-1 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg flex flex-col overflow-hidden min-w-0"
            :class="{ 'flex': showChat, 'hidden lg:flex': !showChat }">

            @if ($selectedPelanggan)
                @php
                    $statusLabel = match ($selectedPelanggan->sesi_aktif) {
                        'human' => 'Admin mengambil alih percakapan',
                        'inventory' => 'Sedang melihat daftar stok',
                        'checkout' => 'Sedang memilih pembayaran QRIS',
                        default => 'Asisten AI aktif',
                    };
                    $statusColor = match ($selectedPelanggan->sesi_aktif) {
                        'human' => 'text-green-500',
                        'checkout' => 'text-amber-500',
                        'inventory' => 'text-sky-500',
                        default => 'text-purple-400',
                    };
                @endphp
                {{-- Header Chat --}}
                <div class="px-4 sm:px-6 py-2.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <button @click="showChat = false"
                            class="lg:hidden shrink-0 p-1.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:text-white rounded-lg hover:bg-gray-700/50 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <div class="relative shrink-0">
                            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-[#2a3343] flex items-center justify-center text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ strtoupper(substr($selectedPelanggan->nama ?? $selectedPelanggan->nomor_wa, 0, 2)) }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white dark:border-[#1e2530] {{ $selectedPelanggan->sesi_aktif === 'human' ? 'bg-green-500' : 'bg-purple-500' }}"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $selectedPelanggan->nama ?? 'Tanpa Nama' }}</p>
                            <p class="text-xs {{ $statusColor }} truncate" id="headerStatusLabel">{{ $statusLabel }}</p>
                        </div>
                    </div>

                    {{-- Toggle Bot ↔ Human --}}
                    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                        <button onclick="clearChat()"
                            class="px-2 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 border border-gray-600 hover:border-gray-500 hover:text-gray-900 dark:text-white rounded-lg transition-colors">
                            Clear
                        </button>
                        <span
                            class="text-xs font-semibold uppercase tracking-wider
                            {{ $selectedPelanggan->sesi_aktif === 'human' ? 'text-green-400' : 'text-purple-400' }}"
                            id="modeLabel">
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
                <div id="chatContainer" class="flex-1 overflow-y-auto px-3 sm:px-6 py-4 space-y-1 bg-gray-50 dark:bg-[#151a22]"
                    ondragover="handleDragOver(event)" ondrop="handleDrop(event)" ondragleave="handleDragLeave(event)">
                    @foreach ($riwayat as $chat)
                        @php
                            $isOutgoing = !empty($chat->pesan_balasan);
                            $isIncoming = !empty($chat->pesan_pengirim);
                            $sumber = $chat->sumber_balasan;
                            $isAdmin = $sumber === 'admin';
                            $isAi = $sumber === 'groq_ai';
                            $isSystem = $sumber === 'system';
                            $isApriori = $sumber === 'apriori';
                            $hasMedia = !empty($chat->media_url);
                        @endphp

                        {{-- Pesan dari pelanggan (rata kiri, abu-abu) --}}
                        @if ($isIncoming)
                            <div class="flex justify-start mb-1.5" data-msg-id="{{ $chat->id }}"
                                data-msg-text="{{ e($chat->pesan_pengirim) }}" data-msg-sender="pelanggan"
                                oncontextmenu="showContextMenu(event, {{ $chat->id }})">
                                <div class="max-w-[85%] sm:max-w-[65%] bg-gray-100 dark:bg-[#2a3343] rounded-2xl rounded-br-2xl px-3 py-2 relative group break-words min-w-0">
                                    {{-- Forward indicator --}}
                                    @if ($chat->is_forwarded)
                                        <div class="flex items-center gap-1 mb-1 text-[10px] text-gray-500 dark:text-gray-400 italic">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            Diteruskan
                                        </div>
                                    @endif

                                    {{-- Reply context --}}
                                    @if ($chat->replyTo)
                                        <div class="mb-1.5 px-2.5 py-1.5 bg-gray-100 dark:bg-[#1a1f2e] border-l-2 border-[#E62C37] rounded text-[11px] cursor-pointer"
                                            onclick="scrollToMsg({{ $chat->replyTo->id }})">
                                            <p class="text-[#E62C37] font-semibold text-[10px]">
                                                {{ $chat->replyTo->sumber_balasan === 'admin' ? 'Admin' : ($chat->replyTo->sumber_balasan === 'groq_ai' ? 'AI Bot' : 'Pelanggan') }}
                                            </p>
                                            <p class="text-gray-500 dark:text-gray-400 truncate">
                                                {{ Str::limit($chat->replyTo->pesan_pengirim ?? ($chat->replyTo->pesan_balasan ?? '[Media]'), 60) }}
                                            </p>
                                        </div>
                                    @endif

                                    {{-- Media --}}
                                    @if ($hasMedia)
                                        @if ($chat->media_type === 'image')
                                            <a href="{{ $chat->media_url }}" target="_blank"
                                                class="block mb-1.5 rounded-lg overflow-hidden">
                                                <img src="{{ $chat->media_url }}" alt="Gambar"
                                                    class="max-w-full max-h-60 rounded-lg object-cover">
                                            </a>
                                        @elseif($chat->media_type === 'video')
                                            <video controls class="max-w-full max-h-60 rounded-lg mb-1.5">
                                                <source src="{{ $chat->media_url }}">
                                            </video>
                                        @else
                                            <a href="{{ $chat->media_url }}" target="_blank"
                                                class="flex items-center gap-2 mb-1.5 px-3 py-2 bg-gray-100 dark:bg-[#1a1f2e] rounded-lg text-gray-600 dark:text-gray-300 text-xs hover:bg-gray-200 dark:hover:bg-[#151a22] transition-colors">
                                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                                {{ basename($chat->media_url) }}
                                            </a>
                                        @endif
                                    @endif

                                    @if (
                                        $chat->pesan_pengirim &&
                                            $chat->pesan_pengirim !== '[Image]' &&
                                            $chat->pesan_pengirim !== '[Video]' &&
                                            $chat->pesan_pengirim !== '[Document]' &&
                                            $chat->pesan_pengirim !== '[Audio]')
                                        <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line break-words">{{ $chat->pesan_pengirim }}
                                        </p>
                                    @endif
                                    <p class="text-[10px] text-gray-400 mt-1 text-right">
                                        {{ $chat->created_at->format('H:i') }}</p>

                                    {{-- Reply button --}}
                                    <button
                                        onclick="setReply({{ $chat->id }}, '{{ e(Str::limit($chat->pesan_pengirim ?? '[Media]', 40)) }}', 'pelanggan')"
                                        class="absolute -right-8 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity p-1 text-gray-500 hover:text-[#E62C37]">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Balasan dari bot/admin/system (rata kanan, admin hijau) --}}
                        @if ($isOutgoing)
                            @php
                                $bubbleClass = $isAdmin
                                    ? 'bg-green-100 dark:bg-green-900/60 rounded-bl-2xl'
                                    : 'bg-white dark:bg-[#1f2733] border border-gray-200 dark:border-gray-700 rounded-bl-2xl';
                            @endphp
                            <div class="flex justify-end mb-1.5" data-msg-id="{{ $chat->id }}"
                                data-msg-text="{{ e($chat->pesan_balasan) }}" data-msg-sender="admin"
                                oncontextmenu="showContextMenu(event, {{ $chat->id }})">
                                <div
                                    class="max-w-[85%] sm:max-w-[65%] rounded-2xl px-3 py-2 relative group break-words min-w-0 {{ $bubbleClass }}">

                                    {{-- Forward indicator --}}
                                    @if ($chat->is_forwarded)
                                        <div class="flex items-center gap-1 mb-1 text-[10px] text-gray-500 dark:text-gray-400 italic">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            Diteruskan
                                        </div>
                                    @endif

                                    {{-- Reply context --}}
                                    @if ($chat->replyTo)
                                        <div class="mb-1.5 px-2.5 py-1.5 bg-black/5 dark:bg-black/20 border-l-2 border-gray-400 dark:border-white/30 rounded text-[11px] cursor-pointer"
                                            onclick="scrollToMsg({{ $chat->replyTo->id }})">
                                            <p class="text-gray-500 dark:text-white/60 font-semibold text-[10px]">
                                                {{ $chat->replyTo->sumber_balasan === 'admin' ? 'Admin' : ($chat->replyTo->sumber_balasan === 'groq_ai' ? 'AI Bot' : 'Pelanggan') }}
                                            </p>
                                            <p class="text-gray-500 dark:text-white/40 truncate">
                                                {{ Str::limit($chat->replyTo->pesan_pengirim ?? ($chat->replyTo->pesan_balasan ?? '[Media]'), 60) }}
                                            </p>
                                        </div>
                                    @endif

                                    @unless ($isAdmin)
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span
                                                class="text-[10px] font-bold uppercase tracking-wider
                                                {{ $isAi ? 'text-purple-500 dark:text-purple-400' : '' }}
                                                {{ $isSystem ? 'text-gray-500 dark:text-gray-400' : '' }}
                                                {{ $isApriori ? 'text-green-600 dark:text-green-400' : '' }}
                                                {{ !$isAi && !$isSystem && !$isApriori ? 'text-gray-500 dark:text-gray-400' : '' }}">
                                                {{ $isAi ? 'AI Bot' : ($isSystem ? 'System' : ($isApriori ? 'Rekomendasi' : 'Bot')) }}
                                            </span>
                                        </div>
                                    @endunless

                                    {{-- Media --}}
                                    @if ($hasMedia)
                                        @if ($chat->media_type === 'image')
                                            <a href="{{ $chat->media_url }}" target="_blank"
                                                class="block mb-1.5 rounded-lg overflow-hidden">
                                                <img src="{{ $chat->media_url }}" alt="Gambar"
                                                    class="max-w-full max-h-60 rounded-lg object-cover">
                                            </a>
                                        @elseif($chat->media_type === 'video')
                                            <video controls class="max-w-full max-h-60 rounded-lg mb-1.5">
                                                <source src="{{ $chat->media_url }}">
                                            </video>
                                        @else
                                            <a href="{{ $chat->media_url }}" target="_blank"
                                                class="flex items-center gap-2 mb-1.5 px-3 py-2 bg-black/20 rounded-lg text-gray-600 dark:text-gray-300 text-xs hover:bg-black/30 transition-colors">
                                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                                {{ basename($chat->media_url) }}
                                            </a>
                                        @endif
                                    @endif

                                    @if (
                                        $chat->pesan_balasan &&
                                            $chat->pesan_balasan !== '[Image]' &&
                                            $chat->pesan_balasan !== '[Video]' &&
                                            $chat->pesan_balasan !== '[Document]' &&
                                            $chat->pesan_balasan !== '[Audio]')
                                        <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line break-words">{{ $chat->pesan_balasan }}
                                        </p>
                                    @endif
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <span
                                            class="text-[10px] text-gray-400">{{ $chat->created_at->format('H:i') }}</span>
                                        @if ($isAdmin)
                                            @if ($chat->terkirim)
                                                <svg class="w-4 h-4 text-green-400" viewBox="0 0 24 16" fill="none"
                                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path d="M1 8l4 4L13 4"></path>
                                                    <path d="M7 8l4 4L19 4"></path>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 text-yellow-400" viewBox="0 0 24 16" fill="none"
                                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                    stroke-linejoin="round">
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

                {{-- Reply preview bar --}}
                <div id="replyBar"
                    class="hidden px-4 py-2 bg-gray-100 dark:bg-[#1a1f2e] border-t border-gray-200 dark:border-gray-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 text-[#E62C37] shrink-0" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                        </svg>
                        <div class="min-w-0">
                            <p id="replyBarSender" class="text-[10px] font-bold text-[#E62C37] uppercase"></p>
                            <p id="replyBarText" class="text-xs text-gray-500 dark:text-gray-400 truncate"></p>
                        </div>
                    </div>
                    <button onclick="cancelReply()" class="p-1 text-gray-500 hover:text-gray-900 dark:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Media preview --}}
                <div id="mediaPreview" class="hidden px-4 py-2 bg-gray-100 dark:bg-[#1a1f2e] border-t border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div id="mediaPreviewContent" class="shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p id="mediaPreviewName" class="text-xs text-gray-600 dark:text-gray-300 truncate"></p>
                            <p id="mediaPreviewSize" class="text-[10px] text-gray-500"></p>
                        </div>
                        <button onclick="cancelMedia()" class="p-1 text-gray-500 hover:text-gray-900 dark:text-white transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Input Kirim Pesan (sticky bottom) --}}
                <div class="sticky bottom-0 z-10 px-3 sm:px-4 py-2.5 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1e2530]" id="inputArea">
                    @php
                        $isAiMode =
                            $selectedPelanggan &&
                            ($selectedPelanggan->sesi_aktif === 'ai' || $selectedPelanggan->sesi_aktif === 'menu');
                    @endphp

                    <div id="aiModeNotice"
                        class="{{ $isAiMode ? '' : 'hidden' }} flex items-center gap-2 px-4 py-2.5 bg-purple-500/10 border border-purple-500/20 rounded-xl">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span class="text-sm text-purple-400">Mode AI aktif — switch ke Human untuk mengirim pesan</span>
                    </div>

                    <form id="chatForm" onsubmit="kirimPesan(event)"
                        class="{{ $isAiMode ? 'hidden' : '' }} flex gap-2 items-end">
                        <input type="file" id="fileInput" class="hidden"
                            accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.mp3"
                            onchange="handleFileSelect(event)">

                        <button type="button" onclick="document.getElementById('fileInput').click()"
                            class="p-2.5 text-gray-500 dark:text-gray-400 hover:text-[#E62C37] transition-colors shrink-0"
                            title="Kirim file">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                            </svg>
                        </button>

                        <div class="flex-1 relative">
                            <input type="text" id="inputPesan" placeholder="Ketik pesan..." autocomplete="off"
                                class="w-full px-4 py-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-[#E62C37]">
                        </div>

                        <button type="submit" id="btnKirim"
                            class="p-3 bg-[#25D366] hover:bg-[#1fb857] text-white rounded-full transition-colors shrink-0 flex items-center justify-center"
                            title="Kirim">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        </button>
                    </form>

                    {{-- Drop zone overlay --}}
                    <div id="dropZone"
                        class="hidden absolute inset-0 bg-[#E62C37]/10 border-2 border-dashed border-[#E62C37] rounded-lg z-50 flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-12 h-12 text-[#E62C37] mx-auto mb-2" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            <p class="text-sm font-semibold text-[#E62C37]">Drop file di sini</p>
                        </div>
                    </div>
                </div>
            @else
                {{-- Placeholder jika belum pilih pelanggan --}}
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor"
                            stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                        <p class="text-gray-500 text-sm">Pilih pelanggan dari daftar untuk memulai percakapan</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Konfirmasi Clear Chat --}}
    <div id="modalClearChat" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Hapus Percakapan</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">Apakah Anda yakin ingin menghapus <span
                    class="font-semibold text-gray-900 dark:text-white">semua percakapan</span> dengan pelanggan ini? Chat yang sudah dihapus
                tidak bisa dikembalikan.</p>
            <div class="flex gap-3 justify-end">
                <button onclick="tutupModalClearChat()"
                    class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                    Batal
                </button>
                <button onclick="konfirmasiClearChat()"
                    class="px-4 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">
                    Ya, Hapus Semua
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Hapus Pesan --}}
    <div id="modalDeleteMsg" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-5">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Hapus Pesan</h3>
            <div class="space-y-2">
                <button onclick="hapusPesan('self')"
                    class="w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#151a22] rounded-xl transition-colors flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Hapus untuk saya
                </button>
                <button onclick="hapusPesan('all')"
                    class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-red-500/10 rounded-xl transition-colors flex items-center gap-3">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Hapus untuk semua
                </button>
            </div>
            <button onclick="tutupModalHapus()"
                class="mt-4 w-full px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                Batal
            </button>
        </div>
    </div>

    {{-- Context Menu Hapus Pesan --}}
    <div id="contextMenuMsg"
        class="fixed z-40 hidden bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-xl shadow-2xl py-1 min-w-[140px]">
        <button onclick="bukaModalHapus()"
            class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:bg-red-500/10 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
            </svg>
            Hapus
        </button>
    </div>

    {{-- Data untuk JavaScript --}}
    <script>
        const SELECTED_PELANGGAN_ID = {{ optional($selectedPelanggan)->id ?? 'null' }};
        const CHAT_SEND_URL = '{{ route('admin.chatbot.chat.send') }}';
        const CHAT_TOGGLE_URL = '{{ route('admin.chatbot.chat.toggle') }}';
        const CHAT_CLEAR_URL = '{{ route('admin.chatbot.chat.clear', ['pelanggan' => '__ID__']) }}';
        const CHAT_DELETE_URL = '{{ route('admin.chatbot.chat.delete', ['pelanggan' => '__ID__']) }}';
        const CHAT_MESSAGES_URL = '{{ route('admin.chatbot.chat.messages', ['pelanggan' => '__ID__']) }}';
        const CHAT_MARK_READ_URL = '{{ route('admin.chatbot.chat.mark-read', ['pelanggan' => '__ID__']) }}';
        const CHAT_UNREAD_URL = '{{ route('admin.chatbot.chat.unread') }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        let currentMode = '{{ optional($selectedPelanggan)->sesi_aktif ?? 'ai' }}';
        let lastMessageId = 0;
        let pollingInterval = null;

        // Reply state
        let replyToId = null;

        // Media state
        let selectedMedia = null;

        // Delete state
        let deleteMsgId = null;

        // Scroll ke bawah
        function scrollToBottom() {
            const container = document.getElementById('chatContainer');
            if (container) container.scrollTop = container.scrollHeight;
        }

        // Scroll ke pesan tertentu
        function scrollToMsg(msgId) {
            const el = document.querySelector(`[data-msg-id="${msgId}"]`);
            if (el) {
                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                el.classList.add('ring-2', 'ring-[#E62C37]', 'ring-offset-2', 'ring-offset-[#1e2530]');
                setTimeout(() => el.classList.remove('ring-2', 'ring-[#E62C37]', 'ring-offset-2', 'ring-offset-[#1e2530]'),
                    2000);
            }
        }

        // Set reply
        function setReply(msgId, text, sender) {
            replyToId = msgId;
            document.getElementById('replyBar').classList.remove('hidden');
            document.getElementById('replyBarSender').textContent = sender === 'pelanggan' ? 'Pelanggan' : 'Admin';
            document.getElementById('replyBarText').textContent = text;
            document.getElementById('inputPesan').focus();
        }

        // Cancel reply
        function cancelReply() {
            replyToId = null;
            document.getElementById('replyBar').classList.add('hidden');
        }

        // Context menu untuk hapus pesan
        function showContextMenu(e, msgId) {
            e.preventDefault();
            e.stopPropagation();
            deleteMsgId = msgId;
            const menu = document.getElementById('contextMenuMsg');
            menu.style.left = e.pageX + 'px';
            menu.style.top = e.pageY + 'px';
            menu.classList.remove('hidden');

            // Tutup menu saat klik di luar
            const closeMenu = function(ev) {
                if (!menu.contains(ev.target)) {
                    menu.classList.add('hidden');
                    document.removeEventListener('click', closeMenu);
                }
            };
            setTimeout(() => document.addEventListener('click', closeMenu), 10);
        }

        // Tutup context menu
        function closeContextMenu() {
            document.getElementById('contextMenuMsg').classList.add('hidden');
        }

        // Buka modal hapus pesan
        function bukaModalHapus() {
            closeContextMenu();
            if (!deleteMsgId) return;
            const modal = document.getElementById('modalDeleteMsg');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Tutup modal hapus pesan
        function tutupModalHapus() {
            deleteMsgId = null;
            const modal = document.getElementById('modalDeleteMsg');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Eksekusi hapus pesan
        async function hapusPesan(mode) {
            if (!deleteMsgId || !SELECTED_PELANGGAN_ID) return;
            tutupModalHapus();

            try {
                const url = CHAT_DELETE_URL.replace('__ID__', SELECTED_PELANGGAN_ID);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({
                        message_id: deleteMsgId,
                        mode: mode,
                    }),
                });

                const data = await res.json();
                if (res.ok && data.status === 'OK') {
                    if (mode === 'self') {
                        // Hapus elemen dari DOM
                        const el = document.querySelector(`[data-msg-id="${deleteMsgId}"]`);
                        if (el) el.remove();
                        showToast('success', 'Berhasil', 'Pesan dihapus untuk Anda');
                    } else {
                        // Update tampilan pesan jadi "telah dihapus"
                        const el = document.querySelector(`[data-msg-id="${deleteMsgId}"]`);
                        if (el) {
                            const inner = el.querySelector('.group > p.text-sm');
                            if (inner) inner.textContent = '🚫 Pesan ini telah dihapus';
                        }
                        showToast('success', 'Berhasil', 'Pesan dihapus untuk semua');
                    }
                } else {
                    showToast('error', 'Gagal', data.message || 'Gagal menghapus pesan');
                }
            } catch (e) {
                showToast('error', 'Gagal', 'Gagal menghapus pesan: ' + e.message);
            }

            deleteMsgId = null;
        }

        // Handle file select
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            showMediaPreview(file);
        }

        // Show media preview
        function showMediaPreview(file) {
            selectedMedia = file;
            const preview = document.getElementById('mediaPreview');
            const content = document.getElementById('mediaPreviewContent');
            const name = document.getElementById('mediaPreviewName');
            const size = document.getElementById('mediaPreviewSize');

            name.textContent = file.name;
            size.textContent = formatFileSize(file.size);

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    content.innerHTML = `<img src="${e.target.result}" class="w-12 h-12 rounded object-cover">`;
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                content.innerHTML =
                    `<div class="w-12 h-12 rounded bg-purple-500/20 flex items-center justify-center"><svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg></div>`;
            } else {
                content.innerHTML =
                    `<div class="w-12 h-12 rounded bg-gray-600/20 flex items-center justify-center"><svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg></div>`;
            }

            preview.classList.remove('hidden');
        }

        // Cancel media
        function cancelMedia() {
            selectedMedia = null;
            document.getElementById('mediaPreview').classList.add('hidden');
            document.getElementById('fileInput').value = '';
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        // Drag & drop (debounced to prevent flickering)
        let dragTimer = null;

        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            if (dragTimer) {
                clearTimeout(dragTimer);
                dragTimer = null;
            }
            const dz = document.getElementById('dropZone');
            dz.classList.remove('hidden');
            dz.classList.add('flex');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            if (dragTimer) clearTimeout(dragTimer);
            dragTimer = setTimeout(() => {
                const dz = document.getElementById('dropZone');
                dz.classList.add('hidden');
                dz.classList.remove('flex');
                dragTimer = null;
            }, 100);
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            if (dragTimer) {
                clearTimeout(dragTimer);
                dragTimer = null;
            }
            const dz = document.getElementById('dropZone');
            dz.classList.add('hidden');
            dz.classList.remove('flex');
            const file = e.dataTransfer.files[0];
            if (file) showMediaPreview(file);
        }

        // Cari pelanggan
        document.getElementById('searchPelanggan')?.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#daftarPelanggan a[data-nama]').forEach(function(el) {
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
                    if (container) container.innerHTML =
                        '<div class="flex items-center justify-center h-full"><p class="text-gray-500 text-sm">Belum ada percakapan.</p></div>';
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
                    showToast('success', 'Berhasil',
                        `Mode diubah ke ${currentMode === 'human' ? 'Admin (Human)' : 'AI Bot'}`);
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

            updateInputArea();
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

            if (['ai', 'menu', 'inventory', 'checkout'].includes(currentMode)) {
                aiNotice.classList.remove('hidden');
                chatForm.classList.add('hidden');
            } else {
                aiNotice.classList.add('hidden');
                chatForm.classList.remove('hidden');
            }
        }

        // Update dot status sesi di sidebar
        function updateSidebarBadge(pelangganId, mode) {
            const link = document.querySelector(`#daftarPelanggan a[data-id="${pelangganId}"]`);
            if (!link) return;

            const dot = link.querySelector('.sesi-dot');
            if (dot) {
                dot.classList.remove('bg-green-500', 'bg-purple-500', 'bg-amber-500', 'bg-sky-500', 'bg-gray-400');
                const color = mode === 'human' ? 'bg-green-500' :
                    mode === 'ai' ? 'bg-purple-500' :
                    mode === 'checkout' ? 'bg-amber-500' :
                    mode === 'inventory' ? 'bg-sky-500' : 'bg-gray-400';
                dot.classList.add(color);
            }

            // Header status label
            const headerLabel = document.getElementById('headerStatusLabel');
            if (headerLabel && String(pelangganId) === String(SELECTED_PELANGGAN_ID)) {
                const labels = {
                    human: 'Admin mengambil alih percakapan',
                    inventory: 'Sedang melihat daftar stok',
                    checkout: 'Sedang memilih pembayaran QRIS',
                };
                headerLabel.textContent = labels[mode] || 'Asisten AI aktif';
            }
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Render reply context HTML
        function renderReplyContext(replyTo) {
            if (!replyTo) return '';
            const senderName = replyTo.sumber_balasan === 'admin' ? 'Admin' : (replyTo.sumber_balasan === 'groq_ai' ?
                'AI Bot' : 'Pelanggan');
            const text = replyTo.pesan_pengirim || replyTo.pesan_balasan || '[Media]';
            return `<div class="mb-1.5 px-2.5 py-1.5 bg-black/5 dark:bg-black/20 border-l-2 border-gray-400 dark:border-white/30 rounded text-[11px] cursor-pointer" onclick="scrollToMsg(${replyTo.id})">
                <p class="text-gray-500 dark:text-white/60 font-semibold text-[10px]">${escapeHtml(senderName)}</p>
                <p class="text-gray-500 dark:text-white/40 truncate">${escapeHtml(text.substring(0, 60))}</p>
            </div>`;
        }

        // Render media HTML
        function renderMedia(msg) {
            if (!msg.media_url) return '';
            if (msg.media_type === 'image') {
                return `<a href="${msg.media_url}" target="_blank" class="block mb-1.5 rounded-lg overflow-hidden"><img src="${msg.media_url}" alt="Gambar" class="max-w-full max-h-60 rounded-lg object-cover"></a>`;
            } else if (msg.media_type === 'video') {
                return `<video controls class="max-w-full max-h-60 rounded-lg mb-1.5"><source src="${msg.media_url}"></video>`;
            } else {
                return `<a href="${msg.media_url}" target="_blank" class="flex items-center gap-2 mb-1.5 px-3 py-2 bg-black/5 dark:bg-black/20 rounded-lg text-gray-600 dark:text-gray-300 text-xs"><svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>${escapeHtml(msg.media_url.split('/').pop())}</a>`;
            }
        }

        // Kirim pesan
        async function kirimPesan(e) {
            e.preventDefault();
            if (!SELECTED_PELANGGAN_ID) return;

            const input = document.getElementById('inputPesan');
            const btn = document.getElementById('btnKirim');
            const pesan = input.value.trim();

            if (!pesan && !selectedMedia) return;

            btn.disabled = true;
            btn.innerHTML =
                '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-dasharray="31.42" stroke-dashoffset="10"/></svg>';

            try {
                const formData = new FormData();
                formData.append('pelanggan_id', SELECTED_PELANGGAN_ID);
                if (pesan) formData.append('pesan', pesan);
                if (replyToId) formData.append('reply_to_id', replyToId);
                if (selectedMedia) formData.append('media', selectedMedia);

                const res = await fetch(CHAT_SEND_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await res.json();
                if (res.ok && data.status === 'OK') {
                    input.value = '';
                    cancelReply();
                    cancelMedia();
                    scrollToBottom();
                    if (data.sent) {
                        showToast('success', 'Terkirim', data.note || 'Pesan terkirim via WhatsApp');
                    } else {
                        showToast('warning', 'Tersimpan', data.note ||
                            'Pesan disimpan tapi belum terkirim ke WhatsApp.');
                    }
                } else {
                    showToast('error', 'Gagal', data.error || data.message || 'Gagal mengirim pesan');
                }
            } catch (err) {
                showToast('error', 'Gagal', 'Gagal mengirim pesan: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML =
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>';
            }
        }

        // Polling pesan baru
        async function pollingPesan() {
            if (!SELECTED_PELANGGAN_ID) return;

            try {
                const url = CHAT_MESSAGES_URL.replace('__ID__', SELECTED_PELANGGAN_ID) + '?after_id=' + lastMessageId;
                const res = await fetch(url);
                const messages = await res.json();

                if (messages.length > 0) {
                    const container = document.getElementById('chatContainer');

                    messages.forEach(function(msg) {
                        if (msg.id > lastMessageId) lastMessageId = msg.id;

                        // Cek duplikat
                        if (document.querySelector('[data-msg-id="' + msg.id + '"]')) return;

                        // Pesan dari pelanggan
                        if (msg.pesan_pengirim) {
                            const div = document.createElement('div');
                            div.className = 'flex justify-start mb-1';
                            div.setAttribute('data-msg-id', msg.id);
                            div.setAttribute('data-msg-text', msg.pesan_pengirim);
                            div.setAttribute('data-msg-sender', 'pelanggan');

                            const forwardHtml = msg.is_forwarded ?
                                '<div class="flex items-center gap-1 mb-1 text-[10px] text-gray-500 dark:text-gray-400 italic"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>Diteruskan</div>' :
                                '';
                            const replyHtml = renderReplyContext(msg.reply_to);
                            const mediaHtml = renderMedia({
                                ...msg,
                                pesan_balasan: null
                            });
                            const textHtml = (msg.pesan_pengirim && !['[Image]', '[Video]', '[Document]',
                                    '[Audio]'
                                ].includes(msg.pesan_pengirim)) ?
                                `<p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line break-words">${escapeHtml(msg.pesan_pengirim)}</p>` :
                                '';

                            div.innerHTML = `
                                <div class="max-w-[85%] sm:max-w-[65%] bg-gray-100 dark:bg-[#2a3343] rounded-2xl rounded-br-2xl px-3 py-2 relative group break-words min-w-0">
                                    ${forwardHtml}${replyHtml}${mediaHtml}${textHtml}
                                    <p class="text-[10px] text-gray-400 mt-1 text-right">${new Date(msg.created_at).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'})}</p>
                                    <button onclick="setReply(${msg.id}, '${escapeHtml((msg.pesan_pengirim || '[Media]').substring(0, 40))}', 'pelanggan')" class="absolute -right-8 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity p-1 text-gray-500 hover:text-[#E62C37]">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                    </button>
                                </div>
                            `;
                            container.appendChild(div);
                        }

                        // Balasan dari bot/admin/system
                        if (msg.pesan_balasan) {
                            const isAdmin = msg.sumber_balasan === 'admin';
                            const isAi = msg.sumber_balasan === 'groq_ai';
                            const isSystem = msg.sumber_balasan === 'system';
                            const isApriori = msg.sumber_balasan === 'apriori';

                            let bubbleClass = 'bg-white dark:bg-[#1f2733] border border-gray-200 dark:border-gray-700 rounded-bl-2xl';
                            let labelColor = 'text-gray-500 dark:text-gray-400';
                            let label = 'Bot';
                            if (isAdmin) {
                                bubbleClass = 'bg-green-100 dark:bg-green-900/60 rounded-bl-2xl';
                            } else if (isAi) {
                                labelColor = 'text-purple-500 dark:text-purple-400';
                                label = 'AI Bot';
                            } else if (isSystem) {
                                labelColor = 'text-gray-500 dark:text-gray-400';
                                label = 'System';
                            } else if (isApriori) {
                                labelColor = 'text-green-600 dark:text-green-400';
                                label = 'Rekomendasi';
                            }

                            let checkmark = '';
                            if (isAdmin) {
                                checkmark = msg.terkirim ?
                                    '<svg class="w-4 h-4 text-green-500" viewBox="0 0 24 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 8l4 4L13 4"></path><path d="M7 8l4 4L19 4"></path></svg>' :
                                    '<svg class="w-4 h-4 text-yellow-500" viewBox="0 0 24 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 8l4 4L13 4"></path></svg>';
                            }

                            const div = document.createElement('div');
                            div.className = 'flex justify-end mb-1.5';
                            div.setAttribute('data-msg-id', msg.id);
                            div.setAttribute('data-msg-text', msg.pesan_balasan);
                            div.setAttribute('data-msg-sender', 'admin');

                            const forwardHtml = msg.is_forwarded ?
                                '<div class="flex items-center gap-1 mb-1 text-[10px] text-gray-500 dark:text-gray-400 italic"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>Diteruskan</div>' :
                                '';
                            const replyHtml = renderReplyContext(msg.reply_to);
                            const mediaHtml = renderMedia(msg);
                            const textHtml = (msg.pesan_balasan && !['[Image]', '[Video]', '[Document]',
                                    '[Audio]'
                                ].includes(msg.pesan_balasan)) ?
                                `<p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line break-words">${escapeHtml(msg.pesan_balasan)}</p>` :
                                '';

                            div.innerHTML = `
                                <div class="max-w-[85%] sm:max-w-[65%] rounded-2xl px-3 py-2 relative group break-words min-w-0 ${bubbleClass}">
                                    ${forwardHtml}${replyHtml}
                                    ${isAdmin ? '' : `<div class="flex items-center gap-1.5 mb-1"><span class="text-[10px] font-bold uppercase tracking-wider ${labelColor}">${label}</span></div>`}
                                    ${mediaHtml}${textHtml}
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <span class="text-[10px] text-gray-400">${new Date(msg.created_at).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'})}</span>
                                        ${checkmark}
                                    </div>
                                </div>
                            `;
                            container.appendChild(div);
                        }
                    });

                    scrollToBottom();
                }
            } catch (e) {
                // Silent fail
            }
        }

        // Polling unread badge di daftar kontak
        async function pollingUnread() {
            try {
                const res = await fetch(CHAT_UNREAD_URL);
                const unreadList = await res.json();
                const map = {};
                unreadList.forEach(function(u) { map[u.pelanggan_id] = u.jumlah; });

                document.querySelectorAll('#daftarPelanggan .unread-badge').forEach(function(badge) {
                    const id = badge.dataset.pelangganId;
                    if (!map[id]) badge.remove();
                });

                unreadList.forEach(function(u) {
                    let badge = document.querySelector(`#daftarPelanggan .unread-badge[data-pelanggan-id="${u.pelanggan_id}"]`);
                    if (!badge) {
                        const link = document.querySelector(`#daftarPelanggan a[data-id="${u.pelanggan_id}"]`);
                        if (!link) return;
                        const previewRow = link.querySelector('.flex-1 > div:nth-child(2)');
                        if (!previewRow) return;
                        badge = document.createElement('span');
                        badge.className = 'unread-badge shrink-0 bg-green-500 text-white text-[10px] font-bold rounded-full min-w-[20px] h-5 px-1.5 inline-flex items-center justify-center';
                        badge.dataset.pelangganId = u.pelanggan_id;
                        previewRow.appendChild(badge);
                    }
                    badge.textContent = u.jumlah;
                });
            } catch (e) {
                // Silent fail
            }
        }

        // Inisialisasi
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            updateInputArea();

            // Tandai pesan sudah dibaca
            if (SELECTED_PELANGGAN_ID) {
                fetch(CHAT_MARK_READ_URL.replace('__ID__', SELECTED_PELANGGAN_ID), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                }).catch(function() {});
            }

            // Cari lastMessageId dari pesan yang sudah ada
            const existingMsgs = document.querySelectorAll('[data-msg-id]');
            existingMsgs.forEach(function(el) {
                const id = parseInt(el.dataset.msgId);
                if (id > lastMessageId) lastMessageId = id;
            });

            // Mulai polling pesan setiap 2 detik
            if (SELECTED_PELANGGAN_ID) {
                pollingInterval = setInterval(pollingPesan, 2000);
            }

            // Polling unread badge setiap 5 detik
            pollingUnread();
            setInterval(pollingUnread, 5000);
        });

        // Hentikan polling saat pindah halaman
        window.addEventListener('beforeunload', function() {
            if (pollingInterval) clearInterval(pollingInterval);
        });
    </script>
@endsection
