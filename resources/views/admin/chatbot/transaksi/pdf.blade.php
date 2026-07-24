<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi - PT 4Putra Vertex Aviary</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1a1a2e; font-size: 12px; line-height: 1.5; }

        .header { background: linear-gradient(135deg, #E62C37 0%, #c5242d 100%); color: white; padding: 30px 40px; }
        .header h1 { font-size: 22px; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px; }
        .header p { font-size: 11px; opacity: 0.9; }
        .header .date { text-align: right; font-size: 11px; opacity: 0.8; margin-top: 8px; }

        .summary { display: flex; gap: 15px; padding: 20px 40px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; }
        .summary-card { flex: 1; background: white; border-radius: 8px; padding: 15px; border: 1px solid #e8e8e8; }
        .summary-card .label { font-size: 10px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
        .summary-card .value { font-size: 18px; font-weight: 700; color: #1a1a2e; margin-top: 4px; }
        .summary-card .value.green { color: #16a34a; }
        .summary-card .value.yellow { color: #ca8a04; }

        .content { padding: 20px 40px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #1a1a2e; color: white; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        thead th:first-child { border-radius: 6px 0 0 0; }
        thead th:last-child { border-radius: 0 6px 0 0; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 11px; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr:hover { background: #f0f4ff; }

        .status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-expired { background: #f3f4f6; color: #6b7280; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .footer { padding: 20px 40px; border-top: 2px solid #E62C37; margin-top: 20px; }
        .footer p { font-size: 10px; color: #888; }
        .footer .company { font-weight: 700; color: #1a1a2e; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1>PT 4PUTRA VERTEX AVIARY</h1>
                <p>Penangkaran Burung Paruh Bengkok Premium — Surabaya Barat</p>
            </div>
            <div class="date">
                <div style="font-size: 14px; font-weight: 700;">LAPORAN TRANSAKSI</div>
                <div>Dicetak: {{ now()->format('d F Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="summary">
        <div class="summary-card">
            <div class="label">Total Transaksi</div>
            <div class="value">{{ $transaksi->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Lunas</div>
            <div class="value green">{{ $transaksi->where('status', 'paid')->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Pending</div>
            <div class="value yellow">{{ $transaksi->where('status', 'pending')->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Pendapatan</div>
            <div class="value green">Rp {{ number_format($transaksi->where('status', 'paid')->sum('total_harga'), 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Order ID</th>
                    <th>Pelanggan</th>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksi as $i => $trx)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-family: monospace; font-size: 10px;">{{ $trx->midtrans_order_id ?? '-' }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $trx->pelanggan->nama ?? '-' }}</div>
                            <div style="color: #888; font-size: 10px;">{{ str_replace(['@s.whatsapp.net', '@lid'], '', $trx->pelanggan->nomor_wa ?? '') }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $trx->inventaris?->nama_spesies ?? '-' }}</div>
                            <div style="color: #888; font-size: 10px;">{{ $trx->inventaris?->fase === 'anakan' ? 'Baby' : ($trx->inventaris?->fase ?? '-') }}</div>
                        </td>
                        <td style="font-weight: 600;">Rp {{ number_format($trx->total_harga, 0, ',', '.') }}</td>
                        <td>
                            @if($trx->status === 'paid')
                                <span class="status status-paid">Lunas</span>
                            @elseif($trx->status === 'pending')
                                <span class="status status-pending">Pending</span>
                            @elseif($trx->status === 'expired')
                                <span class="status status-expired">Kadaluarsa</span>
                            @else
                                <span class="status status-cancelled">Dibatalkan</span>
                            @endif
                        </td>
                        <td style="font-size: 10px; color: #888;">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: #888;">Belum ada transaksi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p class="company">PT 4Putra Vertex Aviary</p>
        <p>Penangkaran Burung Paruh Bengkok Premium — Surabaya Barat, Jawa Timur</p>
        <p style="margin-top: 5px;">Dokumen ini dicetak secara otomatis oleh sistem. Tanda tangan tidak diperlukan.</p>
    </div>
</body>
</html>
