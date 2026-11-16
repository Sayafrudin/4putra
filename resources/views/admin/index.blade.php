@extends('layouts.admin')

@section('content')
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
        Selamat Datang di Dashboard PT 4Putra Vertex Aviary
    </h2>
    <div class="p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800 text-gray-600 dark:text-gray-400">
        Gunakan menu di sebelah kiri untuk mengelola data konten portofolio atau memantau transaksi penjualan e-catalog
        Anda.
    </div>

    @if ($vaksinJatuhTempo->isNotEmpty())
        <div class="mt-6 p-4 bg-white rounded-xl shadow-xs dark:bg-[#151a22] border border-gray-200 dark:border-gray-700">
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
@endsection
