@extends('layouts.admin')

@section('content')
    {{-- ==================== HEADER ==================== --}}
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Dashboard Farm</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ringkasan kondisi farm — {{ now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $usersOnline }} user online</span>
        </div>
    </div>

    {{-- ==================== KARTU RINGKASAN ==================== --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        @if (isset($stats['burung']))
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Burung</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums mt-1">{{ number_format($stats['burung']['total']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dewasa {{ number_format($stats['burung']['dewasa']) }} · Baby {{ number_format($stats['burung']['anakan']) }}</p>
            </div>
        @endif
        @if (isset($stats['keuangan']))
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo Kas Umum</p>
                <p class="text-2xl font-bold tabular-nums mt-1 {{ $stats['keuangan']['saldoKas'] >= 0 ? 'text-green-600' : 'text-[#E62C37]' }}">Rp {{ number_format($stats['keuangan']['saldoKas'], 0, ',', '.') }}</p>
                <a href="{{ route('admin.keuangan.index') }}" class="text-xs text-gray-500 dark:text-gray-400 hover:text-[#E62C37] mt-1 inline-block">Lihat keuangan →</a>
            </div>
        @endif
        @if (isset($stats['stok']))
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Stok Makanan</p>
                <p class="text-2xl font-bold text-amber-500 tabular-nums mt-1">{{ number_format($stats['stok'], 0, ',', '.') }} <span class="text-sm font-semibold">kg</span></p>
                <a href="{{ route('admin.stok.index') }}" class="text-xs text-gray-500 dark:text-gray-400 hover:text-[#E62C37] mt-1 inline-block">Kelola stok →</a>
            </div>
        @endif
        @if (isset($stats['telur']))
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Telur</p>
                <p class="text-2xl font-bold text-blue-500 tabular-nums mt-1">{{ number_format($stats['telur']['diinkubasi']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Inkubator · {{ number_format($stats['telur']['menetas']) }} menetas</p>
            </div>
        @endif
        @if (isset($stats['vaksin']))
            <a href="{{ route('admin.vaksinasi.index') }}" class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl hover:border-[#E62C37]/50 transition-colors">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Butuh Vaksin Ulang</p>
                <p class="text-2xl font-bold tabular-nums mt-1 {{ $stats['vaksin'] > 0 ? 'text-[#E62C37]' : 'text-green-600' }}">{{ number_format($stats['vaksin']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">burung jatuh tempo</p>
            </a>
        @endif
        <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Online Sekarang</p>
            <p class="text-2xl font-bold text-green-500 tabular-nums mt-1">{{ number_format($usersOnline) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">user aktif 5 menit terakhir</p>
        </div>
    </div>

    {{-- ==================== GRAFIK ==================== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
        @if (isset($stats['kas30']))
            <div class="xl:col-span-2 p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200 mb-3">Arus Kas 30 Hari (Kas Umum)</h3>
                <div class="h-64"><canvas id="grafik-kas"></canvas></div>
            </div>
        @endif
        @if (isset($stats['burung']))
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl {{ isset($stats['kas30']) ? '' : 'xl:col-span-3' }}">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200 mb-3">Komposisi Burung</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center mb-1">Per Fase</p>
                        <div class="h-48"><canvas id="grafik-fase"></canvas></div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center mb-1">Per Spesies</p>
                        <div class="h-48"><canvas id="grafik-spesies"></canvas></div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ==================== PANEL DAFTAR ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        @if (isset($stats['transaksiTerbaru']))
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200">Transaksi Terbaru</h3>
                    <a href="{{ route('admin.keuangan.index') }}" class="text-xs font-semibold text-[#E62C37] hover:underline">Semua →</a>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($stats['transaksiTerbaru'] as $t)
                        <li class="py-2.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate">{{ $t['kategori'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t['tanggal'] }}</p>
                            </div>
                            <span class="text-sm font-bold tabular-nums {{ $t['tipe'] === 'masuk' ? 'text-green-600' : 'text-[#E62C37]' }}">
                                {{ $t['tipe'] === 'keluar' ? '-' : '+' }}Rp {{ number_format($t['jumlah'], 0, ',', '.') }}
                            </span>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-gray-500 dark:text-gray-400">Belum ada transaksi.</li>
                    @endforelse
                </ul>
            </div>
        @endif
        @if (isset($stats['aktivitasTerbaru']))
            <div class="p-4 bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200">Aktivitas Harian Terbaru</h3>
                    <a href="{{ route('admin.daily-activities.index') }}" class="text-xs font-semibold text-[#E62C37] hover:underline">Semua →</a>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($stats['aktivitasTerbaru'] as $a)
                        <li class="py-2.5">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate">{{ $a['title'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $a['tanggal'] }}</p>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-gray-500 dark:text-gray-400">Belum ada aktivitas.</li>
                    @endforelse
                </ul>
            </div>
        @endif
    </div>

    {{-- ==================== VAKSIN JATUH TEMPO (widget existing) ==================== --}}
    @if (isset($stats['vaksin']) && $vaksinJatuhTempo->isNotEmpty())
        <div class="mb-6 p-4 bg-white rounded-xl shadow-xs dark:bg-[#151a22] border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200">Perlu Vaksin Ulang
                    ({{ $vaksinJatuhTempo->count() }} burung)</h3>
                <a href="{{ route('admin.vaksinasi.index') }}"
                    class="px-3 py-1.5 text-xs font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-lg transition-colors">Lihat
                    Vaksinasi</a>
            </div>
            <ul class="flex flex-wrap gap-2">
                @foreach ($vaksinJatuhTempo->take(8) as $v)
                    <li class="text-xs font-semibold bg-[#E62C37]/10 text-[#E62C37] px-2.5 py-1.5 rounded-lg">
                        {{ $v->burung->nama }} · terakhir {{ $v->tanggal_vaksin->translatedFormat('d M Y') }}
                    </li>
                @endforeach
                @if ($vaksinJatuhTempo->count() > 8)
                    <li class="text-xs text-gray-500 dark:text-gray-400 self-center">+{{ $vaksinJatuhTempo->count() - 8 }} lainnya</li>
                @endif
            </ul>
        </div>
    @endif

    @php
        // Payload grafik: hanya bagian yang tersedia sesuai hak akses role ini
        $grafikPayload = [
            'kas30' => $stats['kas30'] ?? null,
            'burung' => $stats['burung'] ?? null,
        ];
    @endphp
    <script>
        window.__dashChartsPayload = @json($grafikPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (window.grafikDashboard) {
            window.grafikDashboard(window.__dashChartsPayload);
        } else {
            document.addEventListener('DOMContentLoaded', function () {
                window.grafikDashboard && window.grafikDashboard(window.__dashChartsPayload);
            });
        }
    </script>
@endsection
