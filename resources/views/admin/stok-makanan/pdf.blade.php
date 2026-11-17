<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Makanan - PT 4Putra Vertex Aviary</title>
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
        .summary-card .value.red { color: #E62C37; }
        .content { padding: 20px 40px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #1a1a2e; color: white; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 11px; }
        tbody tr:nth-child(even) { background: #fafafa; }
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
                <p>Penangkaran Burung Paruh Bengkok Premium - Surabaya Barat</p>
            </div>
            <div class="date">
                <div style="font-size: 14px; font-weight: 700;">LAPORAN STOK MAKANAN</div>
                <div>Dicetak: {{ now()->format('d F Y H:i') }}</div>
            </div>
        </div>
    </div>

    @php($perluBeli = $stoks->filter(fn ($s) => $s->perlu_beli))
    <div class="summary">
        <div class="summary-card">
            <div class="label">Total Item Stok</div>
            <div class="value">{{ $stoks->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Perlu Beli Ulang (&lt; 2 minggu)</div>
            <div class="value {{ $perluBeli->isNotEmpty() ? 'red' : '' }}">{{ $perluBeli->count() }} item</div>
        </div>
        <div class="summary-card">
            <div class="label">Stok Terkendali</div>
            <div class="value">{{ $stoks->count() - $perluBeli->count() }} item</div>
        </div>
    </div>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Makanan</th>
                    <th>Stok</th>
                    <th>Konsumsi/Hari</th>
                    <th>Estimasi Habis</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stoks as $i => $s)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-weight: 600;">{{ $s->nama }}</td>
                        <td>{{ $s->jumlah_stok }} {{ $s->satuan }}</td>
                        <td>{{ $s->konsumsi_per_hari }} {{ $s->satuan }}</td>
                        <td style="font-weight: 600; color: {{ $s->perlu_beli ? '#E62C37' : '#16a34a' }};">
                            @if ($s->estimasi_hari !== null)
                                {{ $s->estimasi_hari }} hari ({{ $s->tanggal_beli_ulang->format('d/m/Y') }})
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $s->catatan ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #888;">Belum ada data stok makanan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p class="company">PT 4Putra Vertex Aviary</p>
        <p>Dokumen ini dihasilkan otomatis dari dashboard admin 4PUTRA pada {{ now()->format('d/m/Y H:i') }}.</p>
    </div>
</body>
</html>
