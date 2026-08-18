@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Dashboard Chatbot WhatsApp</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monitoring percakapan, transaksi, dan inventaris chatbot 4PUTRA</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.chatbot.inventaris') }}"
                class="px-4 py-2 text-sm font-bold uppercase tracking-wider text-white bg-amber-600 hover:bg-amber-700 transition-colors rounded-lg">
                Inventaris
            </a>
            <a href="{{ route('admin.chatbot.transaksi') }}"
                class="px-4 py-2 text-sm font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-700 transition-colors rounded-lg">
                Transaksi
            </a>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Pelanggan</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalPelanggan }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sesi Aktif</p>
            <p class="text-2xl font-bold text-green-400 mt-1">{{ $pelangganAktif }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Chat Hari Ini</p>
            <p class="text-2xl font-bold text-blue-400 mt-1">{{ $percakapanHariIni }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notifikasi Baru</p>
            <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $notifikasiBelum }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transaksi Pending</p>
            <p class="text-2xl font-bold text-orange-400 mt-1">{{ $transaksiPending }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pembayaran Berhasil</p>
            <p class="text-2xl font-bold text-emerald-400 mt-1">{{ $transaksiPaid }}</p>
        </div>
    </div>

    {{-- Notifikasi Terbaru --}}
    <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Notifikasi Terbaru</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($notifikasiTerbaru as $notif)
                <div class="px-6 py-4 flex items-start gap-4 {{ !$notif->dibaca ? 'bg-gray-50/80 dark:bg-[#1e2530]/80' : 'bg-white dark:bg-[#151a22]' }}">
                    <div class="shrink-0 mt-1">
                        @if($notif->tipe === 'pembayaran')
                            <span class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                            </span>
                        @elseif($notif->tipe === 'permintaan_manual')
                            <span class="w-8 h-8 rounded-full bg-yellow-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </span>
                        @else
                            <span class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                </svg>
                            </span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $notif->judul }}</p>
                            @if(!$notif->dibaca)
                                <span class="w-2 h-2 rounded-full bg-[#E62C37]"></span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $notif->isi }}</p>
                        @if($notif->pelanggan)
                            @php
                                $nomorDisplay = str_replace(['@s.whatsapp.net', '@lid'], '', $notif->pelanggan->nomor_wa);
                                $isLid = str_contains($notif->pelanggan->nomor_wa, '@lid');
                            @endphp
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $notif->pelanggan->nama ?? '' }}
                                @if(!$notif->pelanggan->nama)
                                    {{ $isLid ? 'ID: ' : '' }}{{ $nomorDisplay }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">{{ $notif->created_at->format('d M H:i') }}</span>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                    Belum ada notifikasi.
                </div>
            @endforelse
        </div>
    </div>
@endsection