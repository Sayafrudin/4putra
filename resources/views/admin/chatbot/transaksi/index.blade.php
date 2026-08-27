@extends('layouts.admin')

@section('content')
    <div class="my-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-5">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white uppercase">Transaksi Chatbot</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daftar transaksi pembayaran pelanggan WhatsApp</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <button onclick="cekStatusSemua()" id="btnRefresh"
                class="px-4 py-2.5 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-colors">
                Refresh Status
            </button>
            <button onclick="bukaModal('modalExportExcel')"
                class="px-4 py-2.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-colors">
                Export Excel
            </button>
            <button onclick="bukaModal('modalExportPdf')"
                class="px-4 py-2.5 text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors">
                Export PDF
            </button>
            <a href="{{ route('admin.chatbot.index') }}"
                class="inline-block px-4 py-2.5 text-sm font-bold text-gray-600 dark:text-gray-300 border border-gray-600 hover:border-gray-500 rounded-xl transition-colors">
                Kembali
            </a>
        </div>
    </div>

    {{-- Input search --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" class="table-search-input w-full bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-[#E62C37] focus:outline-none" placeholder="Cari transaksi...">
        </div>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl px-5 py-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lunas</p>
            <p class="text-2xl font-bold text-green-400 mt-1">{{ $totalPaid ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl px-5 py-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pending</p>
            <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $totalPending ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 rounded-xl px-5 py-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Info auto-refresh --}}
    <div id="statusInfo" class="mb-4 px-4 py-2.5 bg-blue-500/10 border border-blue-500/20 rounded-xl flex items-center gap-2 hidden">
        <svg class="w-4 h-4 text-blue-400 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
        </svg>
        <span class="text-sm text-blue-400" id="statusText">Mengecek status pembayaran...</span>
    </div>

    <div class="w-full overflow-hidden bg-white dark:bg-[#1e2530] border border-gray-200 dark:border-gray-800 shadow-sm mb-10 rounded-xl table-search-wrapper">
        <div class="w-full overflow-x-auto">
            <table class="table-searchable w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-white dark:bg-[#151a22] border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4">Order ID</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">Detail Barang</th>
                        <th class="px-6 py-4 w-20">Qty</th>
                        <th class="px-6 py-4 w-36">Total</th>
                        <th class="px-6 py-4 w-28">Status</th>
                        <th class="px-6 py-4 w-40">Tanggal</th>
                        <th class="px-6 py-4 w-36 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($transaksi as $trx)
                        <tr class="text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#262d3a] transition-colors" data-order-id="{{ $trx->midtrans_order_id }}" data-trx-id="{{ $trx->id }}">
                            <td class="px-6 py-4 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $trx->midtrans_order_id ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $trx->pelanggan->nama ?? '-' }}</div>
                                @php
                                    $nomorRaw = str_replace(['@s.whatsapp.net', '@lid'], '', $trx->pelanggan->nomor_wa ?? '');
                                    $isLid = str_contains($trx->pelanggan->nomor_wa ?? '', '@lid');
                                @endphp
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $isLid ? 'WA ID: ' . $nomorRaw : $nomorRaw }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($trx->inventaris)
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $trx->inventaris->nama_spesies }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $trx->inventaris->fase === 'anakan' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400' }}">
                                            {{ $trx->inventaris->fase === 'anakan' ? 'Baby' : 'Dewasa' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Rp {{ number_format($trx->inventaris->harga, 0, ',', '.') }}/ekor</span>
                                    </div>
                                @else
                                    <span class="text-gray-500 text-xs">Data tidak tersedia</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-center">
                                <span class="px-2 py-1 bg-gray-600/20 rounded-lg text-gray-900 dark:text-white">{{ $trx->quantity ?? 1 }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">Rp {{ number_format($trx->total_harga, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm" id="status-{{ $trx->id }}">
                                @if($trx->status === 'paid')
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-400">Lunas</span>
                                @elseif($trx->status === 'pending')
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-500/20 text-yellow-400">Pending</span>
                                @elseif($trx->status === 'expired')
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/20 text-gray-500 dark:text-gray-400">Kadaluarsa</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-500/20 text-red-400">Dibatalkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">{{ $trx->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Dropdown ubah status --}}
                                    <select onchange="ubahStatus({{ $trx->id }}, this.value)" class="text-xs bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-lg px-2 py-1.5 text-gray-600 dark:text-gray-300 focus:outline-none focus:border-[#E62C37] cursor-pointer">
                                        <option value="" disabled selected>Status</option>
                                        <option value="paid" {{ $trx->status === 'paid' ? 'disabled' : '' }}>Paid</option>
                                        <option value="pending" {{ $trx->status === 'pending' ? 'disabled' : '' }}>Pending</option>
                                        <option value="expired" {{ $trx->status === 'expired' ? 'disabled' : '' }}>Expired</option>
                                        <option value="cancelled" {{ $trx->status === 'cancelled' ? 'disabled' : '' }}>Cancelled</option>
                                    </select>
                                    {{-- Download Invoice --}}
                                    <a href="{{ route('admin.chatbot.transaksi.invoice', $trx->id) }}"
                                        class="px-3 py-1.5 text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 hover:border-blue-500/40 transition-colors"
                                        title="Download Invoice PDF">
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-500">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-10">
        {{ $transaksi->links() }}
    </div>

    {{-- Modal Export PDF --}}
    <div id="modalExportPdf" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Export PDF</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Download laporan transaksi dalam format PDF dengan desain invoice.</p>
            <div class="flex gap-3 justify-center">
                <button onclick="tutupModal('modalExportPdf')"
                    class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                    Batal
                </button>
                <a href="{{ route('admin.chatbot.transaksi.export.pdf') }}" id="btnDownloadPdf"
                    class="px-5 py-2.5 text-sm font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors">
                    Download PDF
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Export Excel --}}
    <div id="modalExportExcel" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85">
        <div class="bg-white dark:bg-[#1e2530] border border-gray-300 dark:border-gray-700 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M12 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M21 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Export Excel</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Download data transaksi dalam format CSV (Excel).</p>
            <div class="flex gap-3 justify-center">
                <button onclick="tutupModal('modalExportExcel')"
                    class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-[#151a22] border border-gray-600 rounded-xl hover:border-gray-500 transition-colors">
                    Batal
                </button>
                <a href="{{ route('admin.chatbot.transaksi.export.excel') }}" id="btnDownloadExcel"
                    class="px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-colors">
                    Download Excel
                </a>
            </div>
        </div>
    </div>

    <script>
        const STATUS_POLLING_URL = '{{ route("admin.chatbot.transaksi.status") }}';
        const UPDATE_STATUS_URL = '{{ url("admin/chatbot/transaksi") }}';
        const CSRF = '{{ csrf_token() }}';

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
        }

        // Ubah status transaksi manual
        async function ubahStatus(trxId, statusBaru) {
            if (!statusBaru) return;

            try {
                const res = await fetch(`${UPDATE_STATUS_URL}/${trxId}/update-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({ status: statusBaru }),
                });

                const data = await res.json();
                if (res.ok && data.status === 'OK') {
                    showToast('success', 'Berhasil', data.message);
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast('error', 'Gagal', data.message || 'Gagal mengubah status');
                }
            } catch (err) {
                showToast('error', 'Error', err.message);
            }
        }

        // Cek status semua transaksi pending
        async function cekStatusSemua() {
            const btn = document.getElementById('btnRefresh');
            const info = document.getElementById('statusInfo');
            const text = document.getElementById('statusText');

            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin mr-2">â³</span> Mengecek...';
            info.classList.remove('hidden');
            text.textContent = 'Mengecek status pembayaran dari Midtrans...';

            try {
                const res = await fetch(STATUS_POLLING_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                });

                const data = await res.json();
                if (res.ok && data.status === 'OK') {
                    if (data.updated > 0) {
                        text.textContent = `âœ… ${data.updated} transaksi berhasil diperbarui! Memuat ulang...`;
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        text.textContent = data.message || 'Semua transaksi pending belum ada perubahan status dari Midtrans.';
                        setTimeout(() => info.classList.add('hidden'), 4000);
                    }
                } else {
                    text.textContent = 'âŒ Gagal: ' + (data.message || 'Unknown error');
                    setTimeout(() => info.classList.add('hidden'), 4000);
                }
            } catch (err) {
                text.textContent = 'âŒ Error: ' + err.message;
                setTimeout(() => info.classList.add('hidden'), 4000);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Refresh Status';
            }
        }

        // Auto-refresh setiap 30 detik
        let autoRefreshInterval = setInterval(async () => {
            try {
                const res = await fetch(STATUS_POLLING_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                });
                const data = await res.json();
                if (res.ok && data.updated > 0) {
                    location.reload();
                }
            } catch (e) {
                // Silent fail
            }
        }, 30000);

        window.addEventListener('beforeunload', () => clearInterval(autoRefreshInterval));
    </script>
@endsection
